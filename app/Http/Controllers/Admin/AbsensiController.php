<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbsensiJamSiswa;
use App\Models\DataKelas;
use App\Models\DataMapel;
use App\Models\DataPembelajaran;
use App\Models\DataSiswa;
use App\Models\DataTahunPelajaran;
use App\Models\JadwalPelajaran;
use App\Models\JamPelajaran;
use App\Models\User;
use App\Support\AbsensiCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AbsensiController extends Controller
{
    public function index(Request $request)
    {
        return redirect()->route('admin.bk.absensi-bulanan.index');
    }

    public function rekap($kelasId, Request $request)
    {
        DataKelas::findOrFail($kelasId);

        return redirect()->route('admin.bk.absensi-bulanan.index', [
            'kelas_id' => $kelasId,
        ]);
    }

    public function jadwal(Request $request)
    {
        if (!$this->absensiTablesReady()) {
            return $this->renderSetupPage();
        }

        $tahunAktif = DataTahunPelajaran::where('status_aktif', 1)->firstOrFail();
        $check = $request->boolean('check');
        $selectedKelasId = (int) $request->get('kelas_id', old('data_kelas_id', 0));

        $kelas = DataKelas::orderBy('nama_kelas')->get();
        $selectedKelas = $kelas->firstWhere('id', $selectedKelasId);
        $slotRules = $this->jadwalSlotRules();
        $pembelajaranOptions = collect();
        $pembelajaranIdByPair = [];
        $jadwalPerSlot = [];

        if ($selectedKelas) {
            $pembelajaranOptions = DataPembelajaran::query()
                ->with(['mapel', 'guru'])
                ->where('data_kelas_id', $selectedKelas->id)
                ->whereHas('mapel')
                ->whereHas('guru.dataGuru')
                ->get()
                ->sortBy(fn($item) => mb_strtolower(
                    ($item->mapel?->nama_mapel ?? '') . ' ' . ($item->guru?->nama ?? '')
                ))
                ->values();

            $pembelajaranIdByPair = $pembelajaranOptions
                ->mapWithKeys(fn($item) => [
                    $item->data_mapel_id . '|' . $item->guru_id => $item->id,
                ])
                ->all();

            $jadwalPerSlot = JadwalPelajaran::query()
                ->with(['mapel', 'guru'])
                ->where('data_tahun_pelajaran_id', $tahunAktif->id)
                ->where('data_kelas_id', $selectedKelas->id)
                ->get()
                ->mapWithKeys(function ($item) {
                    $key = $item->hari . '_' . $item->jam_ke;
                    return [$key => $item];
                })
                ->all();
        }

        $kelengkapanJadwal = $check ? $this->buildJadwalKelengkapan($tahunAktif->id) : [];

        return view('admin.absensi.jadwal', compact(
            'tahunAktif',
            'kelas',
            'check',
            'kelengkapanJadwal',
            'selectedKelas',
            'selectedKelasId',
            'slotRules',
            'pembelajaranOptions',
            'pembelajaranIdByPair',
            'jadwalPerSlot'
        ));
    }

    public function jamPelajaran()
    {
        if (!$this->absensiTablesReady()) {
            return $this->renderSetupPage();
        }

        $polaJam = JamPelajaran::query()
            ->whereIn('hari', ['Senin', 'Jumat'])
            ->orderBy('jam_ke')
            ->get()
            ->mapWithKeys(fn($item) => [
                ($item->hari === 'Jumat' ? 'jumat' : 'senin_kamis') . '_' . $item->jam_ke => $item,
            ])
            ->all();

        $polaRules = [
            'senin_kamis' => [
                'label' => 'Senin–Kamis',
                'jumlah_jam' => 10,
            ],
            'jumat' => [
                'label' => 'Jumat',
                'jumlah_jam' => 8,
            ],
        ];

        return view('admin.absensi.jam-pelajaran', compact('polaRules', 'polaJam'));
    }

    public function jamPelajaranStore(Request $request)
    {
        if (!$this->absensiTablesReady()) {
            return $this->renderSetupPage();
        }

        $request->validate([
            'jam' => 'required|array',
        ]);

        $input = $request->input('jam', []);
        $errors = [];
        $patterns = [
            'senin_kamis' => [
                'label' => 'Senin–Kamis',
                'days' => ['Senin', 'Selasa', 'Rabu', 'Kamis'],
                'hours' => range(1, 10),
            ],
            'jumat' => [
                'label' => 'Jumat',
                'days' => ['Jumat'],
                'hours' => range(1, 8),
            ],
        ];
        $patternRows = [];

        foreach ($patterns as $patternKey => $pattern) {
            $previousEnd = null;

            foreach ($pattern['hours'] as $jamKe) {
                $mulai = trim((string) ($input[$patternKey][$jamKe]['mulai'] ?? ''));
                $selesai = trim((string) ($input[$patternKey][$jamKe]['selesai'] ?? ''));

                if (!$this->isValidTime($mulai) || !$this->isValidTime($selesai)) {
                    $errors[] = "{$pattern['label']} jam ke-{$jamKe}: jam mulai dan selesai wajib diisi.";
                    continue;
                }

                if ($selesai <= $mulai) {
                    $errors[] = "{$pattern['label']} jam ke-{$jamKe}: jam selesai harus setelah jam mulai.";
                    continue;
                }

                if ($previousEnd !== null && $mulai < $previousEnd) {
                    $errors[] = "{$pattern['label']} jam ke-{$jamKe}: waktunya bertabrakan dengan jam sebelumnya.";
                    continue;
                }

                $patternRows[$patternKey][] = [
                    'jam_ke' => $jamKe,
                    'jam_mulai' => $mulai . ':00',
                    'jam_selesai' => $selesai . ':00',
                ];
                $previousEnd = $selesai;
            }
        }

        if ($errors !== []) {
            return back()
                ->withInput()
                ->with('error', implode(' ', array_slice($errors, 0, 5)));
        }

        DB::transaction(function () use ($patterns, $patternRows) {
            foreach ($patterns as $patternKey => $pattern) {
                foreach ($pattern['days'] as $hari) {
                    foreach ($patternRows[$patternKey] as $row) {
                        JamPelajaran::updateOrCreate(
                            ['hari' => $hari, 'jam_ke' => $row['jam_ke']],
                            [
                                'jam_mulai' => $row['jam_mulai'],
                                'jam_selesai' => $row['jam_selesai'],
                                'aktif' => true,
                            ]
                        );
                    }

                    JamPelajaran::where('hari', $hari)
                        ->whereNotIn('jam_ke', $pattern['hours'])
                        ->update(['aktif' => false]);
                }
            }
        });

        return redirect()
            ->route('admin.absensi.jadwal.jam')
            ->with('success', 'Pengaturan jam pelajaran berhasil disimpan.');
    }

    public function jadwalStore(Request $request)
    {
        if (!$this->absensiTablesReady()) {
            return $this->renderSetupPage();
        }

        $tahunAktif = DataTahunPelajaran::where('status_aktif', 1)->firstOrFail();

        $data = $request->validate([
            'data_kelas_id' => 'required|exists:data_kelas,id',
            'slots' => 'nullable|array',
        ]);

        $kelas = DataKelas::findOrFail((int) $data['data_kelas_id']);
        $slotRules = $this->jadwalSlotRules();
        $pembelajaranById = DataPembelajaran::query()
            ->where('data_kelas_id', $kelas->id)
            ->whereHas('mapel')
            ->whereHas('guru.dataGuru')
            ->get()
            ->keyBy('id');

        $existingSlots = JadwalPelajaran::query()
            ->where('data_tahun_pelajaran_id', $tahunAktif->id)
            ->where('data_kelas_id', $kelas->id)
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->hari . '_' . $item->jam_ke => $item];
            })
            ->all();

        $inputSlots = $request->input('slots', []);
        $saved = 0;
        $updated = 0;
        $skippedIncomplete = 0;
        $skippedInvalid = 0;

        DB::beginTransaction();
        try {
            foreach ($slotRules as $hari => $jamList) {
                foreach ($jamList as $jamKe) {
                    $slotKey = $hari . '_' . $jamKe;
                    $slotInput = $inputSlots[$hari][$jamKe] ?? [];
                    $pembelajaranId = (int) ($slotInput['data_pembelajaran_id'] ?? 0);

                    if ($pembelajaranId === 0) {
                        continue;
                    }

                    $pembelajaran = $pembelajaranById->get($pembelajaranId);
                    if (!$pembelajaran) {
                        $skippedInvalid++;
                        continue;
                    }

                    $mapelId = (int) $pembelajaran->data_mapel_id;
                    $guruId = (int) $pembelajaran->guru_id;

                    if (isset($existingSlots[$slotKey])) {
                        $existing = $existingSlots[$slotKey];
                        $sameMapel = (int) $existing->data_mapel_id === $mapelId;
                        $sameGuru = (int) $existing->guru_id === $guruId;

                        if ($sameMapel && $sameGuru) {
                            continue;
                        }

                        $existing->update([
                            'data_mapel_id' => $mapelId,
                            'guru_id' => $guruId,
                        ]);
                        $updated++;
                        continue;
                    }

                    JadwalPelajaran::create([
                        'data_tahun_pelajaran_id' => $tahunAktif->id,
                        'data_kelas_id' => $kelas->id,
                        'data_mapel_id' => $mapelId,
                        'guru_id' => $guruId,
                        'hari' => $hari,
                        'jam_ke' => $jamKe,
                    ]);
                    $saved++;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Jadwal pelajaran gagal disimpan: ' . $e->getMessage());
        }

        $skippedTotal = $skippedIncomplete + $skippedInvalid;
        $message = "Proses simpan jadwal untuk kelas {$kelas->nama_kelas} selesai. "
            . "Ditambah: {$saved}. Diubah: {$updated}. Dilewati: {$skippedTotal}.";

        if ($skippedIncomplete > 0) {
            $message .= " Belum lengkap: {$skippedIncomplete}.";
        }
        if ($skippedInvalid > 0) {
            $message .= " Tidak valid: {$skippedInvalid}.";
        }

        return back()
            ->withInput()
            ->with(($saved + $updated) > 0 ? 'success' : 'error', ($saved + $updated) > 0 ? $message : 'Tidak ada perubahan jadwal yang tersimpan. ' . $message);
    }

    public function jadwalDestroy($id)
    {
        if (!$this->absensiTablesReady()) {
            return $this->renderSetupPage();
        }

        JadwalPelajaran::findOrFail($id)->delete();
        return back()->with('success', 'Jadwal pelajaran berhasil dihapus.');
    }

    public function jadwalExportAssignment(Request $request): StreamedResponse
    {
        if (!$this->absensiTablesReady()) {
            abort(400, 'Modul absensi belum siap.');
        }

        $tahunAktif = DataTahunPelajaran::where('status_aktif', 1)->firstOrFail();
        $kelasId = (int) $request->get('kelas_id', 0);

        $jadwalRows = JadwalPelajaran::query()
            ->with(['guru', 'mapel', 'kelas'])
            ->where('data_tahun_pelajaran_id', $tahunAktif->id)
            ->when($kelasId > 0, fn($query) => $query->where('data_kelas_id', $kelasId))
            ->orderBy('data_kelas_id')
            ->orderByRaw("FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat')")
            ->orderBy('jam_ke')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sheet1');
        $sheet->fromArray(['kelas', 'mapel', 'guru', 'hari', 'jam_ke'], null, 'A1');
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);
        $sheet->getStyle('A1:E1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('DCE6F1');

        $rowNumber = 2;
        foreach ($jadwalRows as $jadwal) {
            $sheet->fromArray([
                $jadwal->kelas->nama_kelas ?? '',
                $jadwal->mapel->nama_mapel ?? '',
                $jadwal->guru->nama ?? '',
                $jadwal->hari,
                $jadwal->jam_ke,
            ], null, 'A' . $rowNumber);

            $rowNumber++;
        }

        $sheet->setAutoFilter('A1:E' . max($rowNumber - 1, 1));
        $sheet->freezePane('A2');

        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $kelasLabel = $kelasId > 0
            ? optional($jadwalRows->first()?->kelas)->nama_kelas
            : 'semua-kelas';
        $kelasLabel = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $kelasLabel) ?: 'jadwal';
        $filename = 'jadwal_absensi_' . strtolower($kelasLabel) . '_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function jadwalDownloadFormatImport(): StreamedResponse
    {
        if (!$this->absensiTablesReady()) {
            abort(400, 'Modul absensi belum siap.');
        }

        $headers = ['kelas', 'mapel', 'guru', 'hari', 'jam_ke'];
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sheet1');

        foreach ($headers as $i => $h) {
            $col = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($col . '1', $h);
        }

        $samplePembelajaran = DataPembelajaran::with(['kelas', 'mapel', 'guru'])
            ->whereHas('guru.dataGuru')
            ->first();
        $sampleKelas = $samplePembelajaran?->kelas?->nama_kelas ?? 'X TKJ 1';
        $sampleMapel = $samplePembelajaran?->mapel?->nama_mapel ?? 'Matematika';
        $sampleGuru = $samplePembelajaran?->guru?->nama ?? 'Nama Guru';

        $samples = [
            [$sampleKelas, $sampleMapel, $sampleGuru, 'Senin', 1],
            [$sampleKelas, $sampleMapel, $sampleGuru, 'Selasa', 3],
        ];

        foreach ($samples as $r => $sample) {
            $rowNo = $r + 2;
            foreach ($sample as $i => $val) {
                $col = Coordinate::stringFromColumnIndex($i + 1);
                $sheet->setCellValue($col . $rowNo, $val);
            }
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
        }, 'format_import_jadwal_absensi.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function jadwalImport(Request $request)
    {
        if (!$this->absensiTablesReady()) {
            return $this->renderSetupPage();
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx',
            'yakin' => 'required|in:1',
        ]);

        $tahunAktif = DataTahunPelajaran::where('status_aktif', 1)->firstOrFail();

        $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
        $sheet = $spreadsheet->getSheetByName('Sheet1') ?? $spreadsheet->getSheet(0);
        $rows = $sheet->toArray(null, true, true, true);

        if (count($rows) < 2) {
            return back()->with('error', 'File kosong / tidak ada data.');
        }

        $headerMap = [];
        foreach (($rows[1] ?? []) as $col => $header) {
            $key = strtolower(trim((string) $header));
            if ($key !== '') {
                $headerMap[$key] = $col;
            }
        }

        $findCol = function (array $aliases) use ($headerMap) {
            foreach ($aliases as $alias) {
                $key = strtolower(trim($alias));
                if (isset($headerMap[$key])) {
                    return $headerMap[$key];
                }
            }
            return null;
        };

        $kelasCol = $findCol(['kelas', 'nama_kelas', 'class']);
        $mapelCol = $findCol(['mapel', 'mata pelajaran', 'mata_pelajaran', 'nama_mapel', 'pelajaran']);
        $guruCol = $findCol(['guru', 'nama_guru', 'guru_pengampu', 'pengampu']);
        $hariCol = $findCol(['hari', 'day']);
        $jamCol = $findCol(['jam_ke', 'jam', 'jam ke', 'sesi', 'slot']);

        if (!$kelasCol || !$mapelCol || !$guruCol || !$hariCol || !$jamCol) {
            return back()->with('error', 'Header wajib tidak ditemukan. Wajib ada: kelas, mapel, guru, hari, jam_ke.');
        }

        $kelasCandidates = DataKelas::select('id', 'nama_kelas')->get()
            ->map(fn($k) => ['id' => (int) $k->id, 'label' => (string) $k->nama_kelas]);

        $mapelRows = DataMapel::select('id', 'nama_mapel', 'singkatan')->get();
        $mapelCandidates = collect();
        foreach ($mapelRows as $m) {
            $mapelCandidates->push(['id' => (int) $m->id, 'label' => (string) $m->nama_mapel]);
            if (!empty($m->singkatan)) {
                $mapelCandidates->push(['id' => (int) $m->id, 'label' => (string) $m->singkatan]);
            }
        }

        $guruCandidates = User::whereHas('dataGuru')
            ->select('id', 'nama')
            ->get()
            ->map(fn($g) => ['id' => (int) $g->id, 'label' => (string) $g->nama]);

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            for ($i = 2; $i <= count($rows); $i++) {
                $row = $rows[$i] ?? null;
                if (!$row) {
                    continue;
                }

                $get = function ($col) use ($row) {
                    $val = $row[$col] ?? null;
                    if (is_string($val)) {
                        $val = trim($val);
                    }
                    return $val === '' ? null : $val;
                };

                $kelasVal = trim((string) ($get($kelasCol) ?? ''));
                $mapelVal = trim((string) ($get($mapelCol) ?? ''));
                $guruVal = trim((string) ($get($guruCol) ?? ''));
                $hariVal = trim((string) ($get($hariCol) ?? ''));
                $jamVal = trim((string) ($get($jamCol) ?? ''));

                if ($kelasVal === '' && $mapelVal === '' && $guruVal === '' && $hariVal === '' && $jamVal === '') {
                    continue;
                }

                if ($kelasVal === '' || $mapelVal === '' || $guruVal === '' || $hariVal === '' || $jamVal === '') {
                    $skipped++;
                    $errors[] = "Baris {$i}: kolom wajib kosong.";
                    continue;
                }

                $hari = $this->normalizeHari($hariVal);
                $jamKe = $this->normalizeJamKe($jamVal);
                if (!$hari || !$jamKe) {
                    $skipped++;
                    $errors[] = "Baris {$i}: hari/jam tidak valid.";
                    continue;
                }
                if ($hari === 'Jumat' && $jamKe > 8) {
                    $skipped++;
                    $errors[] = "Baris {$i}: jam ke-9/10 tidak berlaku untuk Jumat.";
                    continue;
                }

                $kelas = $this->resolveFuzzyCandidate($kelasVal, $kelasCandidates, 70);
                $mapel = $this->resolveFuzzyCandidate($mapelVal, $mapelCandidates, 68);
                $guru = $this->resolveFuzzyCandidate($guruVal, $guruCandidates, 72);

                if (!$kelas || !$mapel || !$guru) {
                    $skipped++;
                    $errors[] = "Baris {$i}: kelas/mapel/guru tidak cocok.";
                    continue;
                }

                $pembelajaranValid = DataPembelajaran::query()
                    ->where('data_kelas_id', (int) $kelas->id)
                    ->where('data_mapel_id', (int) $mapel->id)
                    ->where('guru_id', (int) $guru->id)
                    ->exists();

                if (!$pembelajaranValid) {
                    $skipped++;
                    $errors[] = "Baris {$i}: pasangan kelas, mapel, dan guru tidak terdaftar di Data Pembelajaran.";
                    continue;
                }

                $exists = JadwalPelajaran::where('data_tahun_pelajaran_id', $tahunAktif->id)
                    ->where('data_kelas_id', $kelas->id)
                    ->where('hari', $hari)
                    ->where('jam_ke', $jamKe)
                    ->first();

                if ($exists) {
                    $exists->update([
                        'data_mapel_id' => $mapel->id,
                        'guru_id' => $guru->id,
                    ]);
                    $updated++;
                } else {
                    JadwalPelajaran::create([
                        'data_tahun_pelajaran_id' => $tahunAktif->id,
                        'data_kelas_id' => $kelas->id,
                        'data_mapel_id' => $mapel->id,
                        'guru_id' => $guru->id,
                        'hari' => $hari,
                        'jam_ke' => $jamKe,
                    ]);
                    $created++;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Import gagal: ' . $e->getMessage());
        }

        if ($errors !== []) {
            $preview = implode(' | ', array_slice($errors, 0, 6));
            return back()->with(
                'error',
                "Import selesai. Ditambah: {$created}, Diupdate: {$updated}, Dilewati: {$skipped}. {$preview}"
            );
        }

        return back()->with('success', "Import selesai. Ditambah: {$created}, Diupdate: {$updated}, Dilewati: {$skipped}.");
    }

    private function absensiTablesReady(): bool
    {
        return Schema::hasTable('jam_pelajaran')
            && Schema::hasTable('jadwal_pelajaran')
            && Schema::hasTable('absensi_jam_siswa');
    }

    private function renderSetupPage()
    {
        return response()->view('admin.absensi.setup');
    }

    private function buildRekapRows(int $kelasId, string $startDate, string $endDate, int $tahunId, string $semester): array
    {
        $siswa = DataSiswa::where('data_kelas_id', $kelasId)
            ->orderBy('nama_siswa')
            ->get();

        $records = AbsensiJamSiswa::query()
            ->where('data_kelas_id', $kelasId)
            ->where('data_tahun_pelajaran_id', $tahunId)
            ->where('semester', $semester)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->get()
            ->groupBy('data_siswa_id');

        $rows = [];
        foreach ($siswa as $s) {
            $rows[] = ['siswa' => $s]
                + AbsensiCalculator::summarize($records->get($s->id) ?? collect());
        }

        return $rows;
    }

    private function resolveRange(string $periode, int $bulan, int $quarter, string $semester, int $thAwal, int $thAkhir): array
    {
        $periode = in_array($periode, ['month', 'quarter', 'semester', 'year'], true) ? $periode : 'month';

        if ($periode === 'semester') {
            if ($semester === 'Ganjil') {
                return ["{$thAwal}-07-01", "{$thAwal}-12-31", "Per Semester ({$semester})"];
            }
            return ["{$thAkhir}-01-01", "{$thAkhir}-06-30", "Per Semester ({$semester})"];
        }

        if ($periode === 'year') {
            return ["{$thAwal}-07-01", "{$thAkhir}-06-30", 'Per Tahun Pelajaran'];
        }

        if ($periode === 'quarter') {
            if ($semester === 'Ganjil') {
                if ($quarter === 2) {
                    return ["{$thAwal}-10-01", "{$thAwal}-12-31", 'Per 3 Bulan (Okt-Des)'];
                }
                return ["{$thAwal}-07-01", "{$thAwal}-09-30", 'Per 3 Bulan (Jul-Sep)'];
            }

            if ($quarter === 2) {
                return ["{$thAkhir}-04-01", "{$thAkhir}-06-30", 'Per 3 Bulan (Apr-Jun)'];
            }
            return ["{$thAkhir}-01-01", "{$thAkhir}-03-31", 'Per 3 Bulan (Jan-Mar)'];
        }

        $validMonths = $semester === 'Ganjil' ? [7, 8, 9, 10, 11, 12] : [1, 2, 3, 4, 5, 6];
        if (!in_array($bulan, $validMonths, true)) {
            $bulan = $validMonths[0];
        }

        $year = $bulan >= 7 ? $thAwal : $thAkhir;
        $start = sprintf('%04d-%02d-01', $year, $bulan);
        $end = date('Y-m-t', strtotime($start));
        $label = 'Per 1 Bulan (' . \Carbon\Carbon::parse($start)->translatedFormat('F Y') . ')';

        return [$start, $end, $label];
    }

    private function parseTahunPelajaran(string $tahunPelajaran): array
    {
        if (preg_match('/^(\d{4})\s*\/\s*(\d{4})$/', $tahunPelajaran, $m)) {
            return [(int) $m[1], (int) $m[2]];
        }

        $y = (int) date('Y');
        return [$y, $y + 1];
    }

    private function isMapelValidForKelas(int $kelasId, int $mapelId): bool
    {
        $kelas = DataKelas::find($kelasId);
        if (!$kelas) {
            return false;
        }

        $rawTingkat = strtoupper(trim((string) $kelas->tingkat));
        $mapTingkat = ['10' => 'X', '11' => 'XI', '12' => 'XII', 'X' => 'X', 'XI' => 'XI', 'XII' => 'XII'];
        $tingkatKelas = $mapTingkat[$rawTingkat] ?? $rawTingkat;

        $jurusanId = $kelas->jurusan_id;
        $hasJurusan = !empty($jurusanId);

        return DataMapel::query()
            ->where('id', $mapelId)
            ->whereIn('tingkat', [$tingkatKelas, 'SEMUA'])
            ->where(function ($q) use ($hasJurusan, $jurusanId) {
                if ($hasJurusan) {
                    $q->whereNull('jurusan_id')
                        ->orWhere('jurusan_id', (int) $jurusanId);
                } else {
                    $q->whereNull('jurusan_id');
                }
            })
            ->exists();
    }

    private function normalizeToken(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $text = preg_replace('/[^a-z0-9]+/u', '', $text) ?? $text;
        return $text;
    }

    private function resolveFuzzyCandidate(string $input, $candidates, int $minScore = 70): ?object
    {
        $input = trim($input);
        if ($input === '') {
            return null;
        }

        $needle = $this->normalizeToken($input);
        if ($needle === '') {
            return null;
        }

        $best = null;
        $bestScore = -1.0;

        foreach ($candidates as $row) {
            $label = (string) ($row['label'] ?? '');
            $norm = $this->normalizeToken($label);
            if ($norm === '') {
                continue;
            }

            if ($norm === $needle) {
                return (object) ['id' => (int) $row['id'], 'label' => $label];
            }

            $score = 0.0;
            if (str_contains($norm, $needle) || str_contains($needle, $norm)) {
                $short = min(strlen($norm), strlen($needle));
                $long = max(strlen($norm), strlen($needle));
                $score = $long > 0 ? ($short / $long) * 100 : 0.0;
            } else {
                similar_text($needle, $norm, $percent);
                $score = $percent;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = (object) ['id' => (int) $row['id'], 'label' => $label];
            }
        }

        return ($best && $bestScore >= $minScore) ? $best : null;
    }

    private function normalizeHari(string $value): ?string
    {
        $v = mb_strtolower(trim($value));
        $v = str_replace(["'", '`', '.'], '', $v);
        $map = [
            'senin' => 'Senin',
            'selasa' => 'Selasa',
            'rabu' => 'Rabu',
            'kamis' => 'Kamis',
            'jumat' => 'Jumat',
            "jum'at" => 'Jumat',
            "jumat" => 'Jumat',
            'fri' => 'Jumat',
        ];
        return $map[$v] ?? null;
    }

    private function normalizeJamKe(string $value): ?int
    {
        $v = trim($value);
        if ($v === '') {
            return null;
        }

        if (preg_match('/^\d+$/', $v) === 1) {
            $n = (int) $v;
            return ($n >= 1 && $n <= 10) ? $n : null;
        }

        if (preg_match('/^(\d+)\s*[-\/]\s*(\d+)$/', $v, $m) === 1) {
            $n = (int) $m[1];
            return ($n >= 1 && $n <= 10) ? $n : null;
        }

        return null;
    }

    private function isValidTime(string $value): bool
    {
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value) === 1;
    }

    private function buildJadwalKelengkapan(int $tahunAktifId): array
    {
        $aturanHari = $this->jadwalSlotRules();

        $jadwalRows = JadwalPelajaran::query()
            ->select('data_kelas_id', 'hari', 'jam_ke')
            ->where('data_tahun_pelajaran_id', $tahunAktifId)
            ->get()
            ->groupBy('data_kelas_id');

        $hasil = [];
        foreach (DataKelas::query()->orderBy('nama_kelas')->get() as $kelas) {
            $terisiPerHari = [];
            foreach (($jadwalRows->get($kelas->id) ?? collect()) as $item) {
                $hari = (string) $item->hari;
                $jamKe = (int) $item->jam_ke;
                if (!isset($terisiPerHari[$hari])) {
                    $terisiPerHari[$hari] = [];
                }

                $terisiPerHari[$hari][$jamKe] = true;
            }

            $detailKosong = [];
            $totalTerisi = 0;
            $totalWajib = 0;

            foreach ($aturanHari as $hari => $jamWajib) {
                $terisi = array_map('intval', array_keys($terisiPerHari[$hari] ?? []));
                sort($terisi);

                $kosong = array_values(array_diff($jamWajib, $terisi));
                $jumlahTerisiHari = count(array_intersect($jamWajib, $terisi));

                $totalTerisi += $jumlahTerisiHari;
                $totalWajib += count($jamWajib);

                if ($kosong !== []) {
                    $detailKosong[] = [
                        'hari' => $hari,
                        'jam' => $kosong,
                    ];
                }
            }

            $hasil[] = [
                'kelas' => $kelas,
                'lengkap' => $detailKosong === [],
                'total_terisi' => $totalTerisi,
                'total_wajib' => $totalWajib,
                'detail_kosong' => $detailKosong,
            ];
        }

        return $hasil;
    }

    private function jadwalSlotRules(): array
    {
        return [
            'Senin' => range(1, 10),
            'Selasa' => range(1, 10),
            'Rabu' => range(1, 10),
            'Kamis' => range(1, 10),
            'Jumat' => range(1, 8),
        ];
    }

    private function getMapelOptionsForKelas(DataKelas $kelas)
    {
        $rawTingkat = strtoupper(trim((string) $kelas->tingkat));
        $mapTingkat = ['10' => 'X', '11' => 'XI', '12' => 'XII', 'X' => 'X', 'XI' => 'XI', 'XII' => 'XII'];
        $tingkatKelas = $mapTingkat[$rawTingkat] ?? $rawTingkat;
        $jurusanId = $kelas->jurusan_id;
        $hasJurusan = !empty($jurusanId);

        return DataMapel::query()
            ->whereIn('tingkat', [$tingkatKelas, 'SEMUA'])
            ->where(function ($q) use ($hasJurusan, $jurusanId) {
                if ($hasJurusan) {
                    $q->whereNull('jurusan_id')
                        ->orWhere('jurusan_id', (int) $jurusanId);
                    return;
                }

                $q->whereNull('jurusan_id');
            })
            ->orderBy('nama_mapel')
            ->get();
    }
}
