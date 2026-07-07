<?php

namespace App\Http\Controllers\Guru\WaliKelas;

use App\Http\Controllers\Controller;
use App\Models\DataKelas;
use App\Models\DataKetidakhadiran;
use App\Models\DataSiswa;
use App\Models\DataTahunPelajaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AbsensiController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $limit = (int) $request->get('limit', 10);
        if (!in_array($limit, [10, 25, 50, 100], true)) {
            $limit = 10;
        }

        $q = trim((string) $request->get('q', ''));
        $tahunAktif = DataTahunPelajaran::where('status_aktif', 1)->first();

        $kelas = DataKelas::query()
            ->withCount('siswa')
            ->with('wali.pengguna')
            ->where('wali_kelas_id', $user->id)
            ->when($q !== '', fn($query) => $query->where('nama_kelas', 'like', "%{$q}%"))
            ->orderBy('nama_kelas')
            ->paginate($limit)
            ->withQueryString();

        return view('guru.wali_kelas.absensi.index', compact('kelas', 'limit', 'q', 'tahunAktif'));
    }

    public function kelola($kelasId, Request $request)
    {
        $user = Auth::user();
        $kelas = DataKelas::with('wali.pengguna')
            ->where('id', $kelasId)
            ->where('wali_kelas_id', $user->id)
            ->firstOrFail();

        $tahunAktif = DataTahunPelajaran::where('status_aktif', 1)->firstOrFail();

        $q = trim((string) $request->get('q', ''));
        $siswa = DataSiswa::query()
            ->where('data_kelas_id', $kelas->id)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('nama_siswa', 'like', "%{$q}%")
                        ->orWhere('nis', 'like', "%{$q}%");
                });
            })
            ->orderBy('nama_siswa')
            ->get();

        $data = DataKetidakhadiran::where('data_tahun_pelajaran_id', $tahunAktif->id)
            ->where('semester', $tahunAktif->semester)
            ->whereIn('data_siswa_id', $siswa->pluck('id'))
            ->get()
            ->keyBy('data_siswa_id');

        return view('guru.wali_kelas.absensi.kelola', compact(
            'kelas',
            'tahunAktif',
            'siswa',
            'data',
            'q'
        ));
    }

    public function update(Request $request, $kelasId)
    {
        $user = Auth::user();
        $kelas = DataKelas::where('id', $kelasId)
            ->where('wali_kelas_id', $user->id)
            ->firstOrFail();

        $tahunAktif = DataTahunPelajaran::where('status_aktif', 1)->firstOrFail();
        $siswaIds = DataSiswa::where('data_kelas_id', $kelas->id)->pluck('id')->map(fn($id) => (int) $id)->all();
        $allowedSiswa = array_flip($siswaIds);

        $sakit = $request->input('sakit', []);
        $izin = $request->input('izin', []);
        $alpa = $request->input('alpa', []);

        foreach ($siswaIds as $siswaId) {
            $s = max(0, (int) ($sakit[$siswaId] ?? 0));
            $i = max(0, (int) ($izin[$siswaId] ?? 0));
            $a = max(0, (int) ($alpa[$siswaId] ?? 0));

            if (!isset($allowedSiswa[$siswaId])) {
                continue;
            }

            DataKetidakhadiran::updateOrCreate(
                [
                    'data_siswa_id' => $siswaId,
                    'data_tahun_pelajaran_id' => $tahunAktif->id,
                    'semester' => $tahunAktif->semester,
                ],
                [
                    'sakit' => $s,
                    'izin' => $i,
                    'tanpa_keterangan' => $a,
                ]
            );
        }

        return back()->with('success', 'Absensi berhasil disimpan.');
    }
}

