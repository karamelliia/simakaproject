<?php

namespace App\Http\Controllers\BK;

use App\Http\Controllers\Controller;
use App\Models\AbsensiJamSiswa;
use App\Models\DataKelas;
use App\Models\DataSiswa;
use App\Models\DataTahunPelajaran;
use App\Support\AbsensiCalculator;
use Illuminate\Http\Request;

class AbsensiBulananController extends Controller
{
    public function index(Request $request)
    {
        $tahunAktif = DataTahunPelajaran::where('status_aktif', 1)->first();

        $periode = strtolower((string) $request->get('periode', 'bulan'));
        if (!in_array($periode, ['bulan', 'triwulan', 'semester', 'tahun'], true)) {
            $periode = 'bulan';
        }

        $bulanInput = (int) $request->get('bulan', (int) date('n'));
        $tahunInput = (int) $request->get('tahun', (int) date('Y'));
        $triwulan = (int) $request->get('triwulan', 1);
        if ($bulanInput < 1 || $bulanInput > 12) {
            $bulanInput = (int) date('n');
        }
        if ($triwulan < 1 || $triwulan > 4) {
            $triwulan = 1;
        }

        $q = trim((string) $request->get('q', ''));
        $kelasId = $request->get('kelas_id');

        [$startDate, $endDate, $periodeLabel] = $this->resolvePeriod(
            $periode,
            $bulanInput,
            $tahunInput,
            $triwulan,
            $tahunAktif
        );

        $siswaQuery = DataSiswa::query()
            ->with('kelas')
            ->whereRaw("UPPER(COALESCE(status_siswa, 'AKTIF')) = 'AKTIF'")
            ->when($kelasId, fn($builder) => $builder->where('data_kelas_id', $kelasId))
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($w) use ($q) {
                    $w->where('nama_siswa', 'like', "%{$q}%")
                        ->orWhere('nis', 'like', "%{$q}%")
                        ->orWhere('nisn', 'like', "%{$q}%");
                });
            })
            ->orderBy('nama_siswa');

        $siswa = $siswaQuery->get();

        $records = AbsensiJamSiswa::query()
            ->when($tahunAktif, fn($builder) => $builder->where('data_tahun_pelajaran_id', $tahunAktif->id))
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->when($kelasId, fn($builder) => $builder->where('data_kelas_id', $kelasId))
            ->whereIn('data_siswa_id', $siswa->pluck('id'))
            ->get()
            ->groupBy('data_siswa_id');

        $rows = [];
        foreach ($siswa as $s) {
            $rows[] = ['siswa' => $s]
                + AbsensiCalculator::summarize($records->get($s->id) ?? collect());
        }

        usort($rows, fn($x, $y) => ($y['alpa'] <=> $x['alpa']) ?: ($y['izin'] <=> $x['izin']));

        $ringkasan = [
            'total_siswa' => count($rows),
            'total_hadir' => array_sum(array_column($rows, 'hadir')),
            'total_sakit' => array_sum(array_column($rows, 'sakit')),
            'total_izin' => array_sum(array_column($rows, 'izin')),
            'total_alpa' => array_sum(array_column($rows, 'alpa')),
            'total_jam' => array_sum(array_column($rows, 'total_jam')),
        ];

        $kelasOptions = DataKelas::orderBy('nama_kelas')->get();

        return view('bk.absensi_bulanan.index', [
            'rows' => $rows,
            'ringkasan' => $ringkasan,
            'kelasOptions' => $kelasOptions,
            'tahunAktif' => $tahunAktif,
            'bulan' => $bulanInput,
            'tahun' => $tahunInput,
            'periode' => $periode,
            'triwulan' => $triwulan,
            'kelasId' => $kelasId,
            'q' => $q,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'periodeLabel' => $periodeLabel,
        ]);
    }

    private function resolvePeriod(
        string $periode,
        int $bulan,
        int $tahun,
        int $triwulan,
        ?DataTahunPelajaran $tahunAktif
    ): array {
        if ($periode === 'triwulan') {
            $startMonth = (($triwulan - 1) * 3) + 1;
            $start = sprintf('%04d-%02d-01', $tahun, $startMonth);
            $end = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $tahun, $startMonth + 2)));

            return [$start, $end, "Triwulan {$triwulan} Tahun {$tahun}"];
        }

        if (in_array($periode, ['semester', 'tahun'], true) && $tahunAktif) {
            [$awal, $akhir] = $this->parseSchoolYear((string) $tahunAktif->tahun_pelajaran);

            if ($periode === 'tahun') {
                return [
                    "{$awal}-07-01",
                    "{$akhir}-06-30",
                    "Tahun Pelajaran {$tahunAktif->tahun_pelajaran}",
                ];
            }

            if (strcasecmp((string) $tahunAktif->semester, 'Genap') === 0) {
                return [
                    "{$akhir}-01-01",
                    "{$akhir}-06-30",
                    "Semester Genap {$tahunAktif->tahun_pelajaran}",
                ];
            }

            return [
                "{$awal}-07-01",
                "{$awal}-12-31",
                "Semester Ganjil {$tahunAktif->tahun_pelajaran}",
            ];
        }

        $start = sprintf('%04d-%02d-01', $tahun, $bulan);
        $end = date('Y-m-t', strtotime($start));

        return [$start, $end, \Carbon\Carbon::parse($start)->translatedFormat('F Y')];
    }

    private function parseSchoolYear(string $schoolYear): array
    {
        if (preg_match('/(\d{4})\D+(\d{4})/', $schoolYear, $matches)) {
            return [(int) $matches[1], (int) $matches[2]];
        }

        $year = (int) date('Y');
        return [$year, $year + 1];
    }
}
