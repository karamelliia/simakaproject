<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\AbsensiJamSiswa;
use App\Models\DataSiswa;
use App\Models\DataTahunPelajaran;
use App\Models\JadwalPelajaran;
use App\Models\JamPelajaran;
use App\Services\AbsensiSyncService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class AbsensiController extends Controller
{
    public function __construct(private readonly AbsensiSyncService $syncService)
    {
    }

    public function index(Request $request)
    {
        if (!$this->absensiTablesReady()) {
            return back()->with('error', 'Modul Absensi belum siap. Jalankan migrasi database terlebih dahulu.');
        }

        $tanggal = now()->toDateString();
        $hari = $this->hariIndonesiaFromDate($tanggal);
        $jamSekarang = now()->format('H:i:s');
        $slotAktif = null;
        if ($hari) {
            $slotAktif = JamPelajaran::query()
                ->where('hari', $hari)
                ->where('aktif', true)
                ->whereNotNull('jam_mulai')
                ->whereNotNull('jam_selesai')
                ->where('jam_mulai', '<=', $jamSekarang)
                ->where('jam_selesai', '>=', $jamSekarang)
                ->orderBy('jam_ke')
                ->first();
        }

        $tahunAktif = DataTahunPelajaran::where('status_aktif', 1)->first();
        $jadwal = collect();

        if ($slotAktif) {
            $jadwal = JadwalPelajaran::query()
                ->with(['kelas', 'mapel'])
                ->where('guru_id', Auth::id())
                ->when($tahunAktif, fn($q) => $q->where('data_tahun_pelajaran_id', $tahunAktif->id))
                ->where('hari', $hari)
                ->where('jam_ke', $slotAktif->jam_ke)
                ->orderBy('jam_ke')
                ->get();
        }

        return view('guru.absensi.index', compact('jadwal', 'tanggal', 'hari', 'tahunAktif', 'slotAktif', 'jamSekarang'));
    }

    public function input($jadwalId, Request $request)
    {
        if (!$this->absensiTablesReady()) {
            return back()->with('error', 'Modul Absensi belum siap. Jalankan migrasi database terlebih dahulu.');
        }

        $jadwal = JadwalPelajaran::with(['kelas', 'mapel'])->findOrFail($jadwalId);
        abort_unless((int) $jadwal->guru_id === (int) Auth::id(), 403);

        $tanggal = $request->get('tanggal', date('Y-m-d'));
        $hari = $this->hariIndonesiaFromDate($tanggal);
        abort_unless($hari === $jadwal->hari, 422, 'Tanggal tidak sesuai hari jadwal.');

        $siswa = DataSiswa::where('data_kelas_id', $jadwal->data_kelas_id)
            ->orderBy('nama_siswa')
            ->get();

        $existing = AbsensiJamSiswa::query()
            ->where('tanggal', $tanggal)
            ->where('data_kelas_id', $jadwal->data_kelas_id)
            ->where('jam_ke', $jadwal->jam_ke)
            ->whereIn('data_siswa_id', $siswa->pluck('id'))
            ->get()
            ->keyBy('data_siswa_id');

        $slotJadwal = JamPelajaran::query()
            ->where('hari', $jadwal->hari)
            ->where('jam_ke', $jadwal->jam_ke)
            ->where('aktif', true)
            ->first();

        return view('guru.absensi.input', compact('jadwal', 'tanggal', 'hari', 'siswa', 'existing', 'slotJadwal'));
    }

    public function store($jadwalId, Request $request)
    {
        if (!$this->absensiTablesReady()) {
            return back()->with('error', 'Modul Absensi belum siap. Jalankan migrasi database terlebih dahulu.');
        }

        $jadwal = JadwalPelajaran::findOrFail($jadwalId);
        abort_unless((int) $jadwal->guru_id === (int) Auth::id(), 403);

        $data = $request->validate([
            'tanggal' => 'required|date',
            'status' => 'required|array',
            'status.*' => 'required|in:H,S,I,A',
            'catatan' => 'nullable|array',
            'catatan.*' => 'nullable|string|max:255',
        ]);

        $hari = $this->hariIndonesiaFromDate($data['tanggal']);
        abort_unless($hari === $jadwal->hari, 422, 'Tanggal tidak sesuai hari jadwal.');

        $tahunAktif = DataTahunPelajaran::where('status_aktif', 1)->firstOrFail();
        $validSiswa = DataSiswa::where('data_kelas_id', $jadwal->data_kelas_id)->pluck('id')->map(fn($id) => (int) $id)->all();
        $lookup = array_flip($validSiswa);

        foreach ($data['status'] as $siswaId => $status) {
            $sid = (int) $siswaId;
            if (!isset($lookup[$sid])) {
                continue;
            }

            AbsensiJamSiswa::updateOrCreate(
                [
                    'tanggal' => $data['tanggal'],
                    'data_siswa_id' => $sid,
                    'jam_ke' => (int) $jadwal->jam_ke,
                ],
                [
                    'data_tahun_pelajaran_id' => $tahunAktif->id,
                    'semester' => $tahunAktif->semester,
                    'data_kelas_id' => (int) $jadwal->data_kelas_id,
                    'data_mapel_id' => (int) $jadwal->data_mapel_id,
                    'guru_id' => (int) Auth::id(),
                    'hari' => $jadwal->hari,
                    'status' => $status,
                    'catatan' => $data['catatan'][$siswaId] ?? null,
                ]
            );
        }

        $this->syncService->syncKelasSemester(
            (int) $jadwal->data_kelas_id,
            (int) $tahunAktif->id,
            (string) $tahunAktif->semester
        );

        return redirect()
            ->route('guru.absensi.input', ['jadwal' => $jadwal->id, 'tanggal' => $data['tanggal']])
            ->with('success', 'Absensi jam pelajaran berhasil disimpan.');
    }

    public function rekap(Request $request)
    {
        if (!$this->absensiTablesReady()) {
            return back()->with('error', 'Modul Absensi belum siap. Jalankan migrasi database terlebih dahulu.');
        }

        $request->validate([
            'periode' => 'nullable|in:bulan_ini,3_bulan,semester,custom',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'kelas_id' => 'nullable|integer|exists:data_kelas,id',
            'mapel_id' => 'nullable|integer|exists:data_mapel,id',
        ]);

        $guruId = (int) Auth::id();
        $periode = $request->get('periode', '3_bulan');
        [$tanggalMulai, $tanggalSelesai] = $this->resolveRekapDates($request, $periode);
        $kelasId = $request->integer('kelas_id') ?: null;
        $mapelId = $request->integer('mapel_id') ?: null;

        $baseQuery = AbsensiJamSiswa::query()
            ->where('guru_id', $guruId)
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])
            ->when($kelasId, fn ($query) => $query->where('data_kelas_id', $kelasId))
            ->when($mapelId, fn ($query) => $query->where('data_mapel_id', $mapelId));

        $rekap = (clone $baseQuery)
            ->join('data_siswa', 'data_siswa.id', '=', 'absensi_jam_siswa.data_siswa_id')
            ->join('data_kelas', 'data_kelas.id', '=', 'absensi_jam_siswa.data_kelas_id')
            ->join('data_mapel', 'data_mapel.id', '=', 'absensi_jam_siswa.data_mapel_id')
            ->select([
                'absensi_jam_siswa.data_siswa_id',
                'absensi_jam_siswa.data_kelas_id',
                'absensi_jam_siswa.data_mapel_id',
                'data_siswa.nama_siswa',
                'data_siswa.nis',
                'data_kelas.nama_kelas',
                'data_mapel.nama_mapel',
            ])
            ->selectRaw('SUM(CASE WHEN absensi_jam_siswa.status = "H" THEN 1 ELSE 0 END) AS hadir')
            ->selectRaw('SUM(CASE WHEN absensi_jam_siswa.status = "S" THEN 1 ELSE 0 END) AS sakit')
            ->selectRaw('SUM(CASE WHEN absensi_jam_siswa.status = "I" THEN 1 ELSE 0 END) AS izin')
            ->selectRaw('SUM(CASE WHEN absensi_jam_siswa.status = "A" THEN 1 ELSE 0 END) AS alpa')
            ->selectRaw('COUNT(*) AS total_absensi')
            ->selectRaw('COUNT(DISTINCT CONCAT(absensi_jam_siswa.tanggal, "-", absensi_jam_siswa.jam_ke)) AS total_pertemuan')
            ->groupBy([
                'absensi_jam_siswa.data_siswa_id',
                'absensi_jam_siswa.data_kelas_id',
                'absensi_jam_siswa.data_mapel_id',
                'data_siswa.nama_siswa',
                'data_siswa.nis',
                'data_kelas.nama_kelas',
                'data_mapel.nama_mapel',
            ])
            ->orderBy('data_kelas.nama_kelas')
            ->orderBy('data_mapel.nama_mapel')
            ->orderBy('data_siswa.nama_siswa')
            ->paginate(25)
            ->withQueryString();

        $totals = (clone $baseQuery)
            ->selectRaw('COUNT(DISTINCT CONCAT(tanggal, "-", data_kelas_id, "-", data_mapel_id, "-", jam_ke)) AS pertemuan')
            ->selectRaw('COUNT(DISTINCT data_siswa_id) AS siswa')
            ->selectRaw('SUM(CASE WHEN status = "H" THEN 1 ELSE 0 END) AS hadir')
            ->selectRaw('SUM(CASE WHEN status = "S" THEN 1 ELSE 0 END) AS sakit')
            ->selectRaw('SUM(CASE WHEN status = "I" THEN 1 ELSE 0 END) AS izin')
            ->selectRaw('SUM(CASE WHEN status = "A" THEN 1 ELSE 0 END) AS alpa')
            ->first();

        $kelasOptions = JadwalPelajaran::query()
            ->with('kelas')
            ->where('guru_id', $guruId)
            ->get()
            ->pluck('kelas')
            ->filter()
            ->unique('id')
            ->sortBy('nama_kelas')
            ->values();

        $mapelOptions = JadwalPelajaran::query()
            ->with('mapel')
            ->where('guru_id', $guruId)
            ->get()
            ->pluck('mapel')
            ->filter()
            ->unique('id')
            ->sortBy('nama_mapel')
            ->values();

        return view('guru.absensi.rekap', compact(
            'rekap',
            'totals',
            'kelasOptions',
            'mapelOptions',
            'periode',
            'tanggalMulai',
            'tanggalSelesai',
            'kelasId',
            'mapelId'
        ));
    }

    private function resolveRekapDates(Request $request, string $periode): array
    {
        $today = Carbon::today();

        return match ($periode) {
            'bulan_ini' => [
                $today->copy()->startOfMonth()->toDateString(),
                $today->copy()->endOfMonth()->toDateString(),
            ],
            'semester' => [
                $today->month >= 7
                    ? $today->copy()->startOfYear()->addMonths(6)->toDateString()
                    : $today->copy()->startOfYear()->toDateString(),
                $today->toDateString(),
            ],
            'custom' => [
                Carbon::parse($request->get('tanggal_mulai', $today->copy()->subMonths(3)->toDateString()))->toDateString(),
                Carbon::parse($request->get('tanggal_selesai', $today->toDateString()))->toDateString(),
            ],
            default => [
                $today->copy()->subMonths(3)->toDateString(),
                $today->toDateString(),
            ],
        };
    }

    private function hariIndonesiaFromDate(string $tanggal): ?string
    {
        $en = date('l', strtotime($tanggal));
        return match ($en) {
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            default => null,
        };
    }

    private function absensiTablesReady(): bool
    {
        return Schema::hasTable('jam_pelajaran')
            && Schema::hasTable('jadwal_pelajaran')
            && Schema::hasTable('absensi_jam_siswa');
    }
}
