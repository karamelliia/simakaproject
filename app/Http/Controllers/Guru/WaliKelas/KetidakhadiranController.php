<?php

namespace App\Http\Controllers\Guru\WaliKelas;

use App\Http\Controllers\Controller;
use App\Models\DataKelas;
use App\Models\DataKetidakhadiran;
use App\Models\DataSiswa;
use App\Models\DataTahunPelajaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KetidakhadiranController extends Controller
{
    public function index()
    {
        return redirect()->route('guru.wali-kelas.absensi.index');
    }

    public function kelola($kelasId)
    {
        return redirect()->route('guru.wali-kelas.absensi.kelola', $kelasId);
    }

    public function update(Request $request, $kelasId)
    {
        $user = Auth::user();
        $kelas = DataKelas::where('id', $kelasId)
            ->where('wali_kelas_id', $user->id)
            ->firstOrFail();

        $tahunAktif = DataTahunPelajaran::where('status_aktif', 1)->firstOrFail();
        $siswaIds = DataSiswa::where('data_kelas_id', $kelas->id)->pluck('id');

        $sakit = $request->input('sakit', []);
        $izin = $request->input('izin', []);
        $alpa = $request->input('alpa', $request->input('tanpa_keterangan', []));

        foreach ($siswaIds as $siswaId) {
            DataKetidakhadiran::updateOrCreate(
                [
                    'data_siswa_id' => $siswaId,
                    'data_tahun_pelajaran_id' => $tahunAktif->id,
                    'semester' => $tahunAktif->semester,
                ],
                [
                    'sakit' => max(0, (int) ($sakit[$siswaId] ?? 0)),
                    'izin' => max(0, (int) ($izin[$siswaId] ?? 0)),
                    'tanpa_keterangan' => max(0, (int) ($alpa[$siswaId] ?? 0)),
                ]
            );
        }

        return redirect()->route('guru.wali-kelas.absensi.kelola', $kelasId)
            ->with('success', 'Absensi berhasil disimpan.');
    }
}
