<?php

namespace App\Services;

use App\Models\AbsensiJamSiswa;
use App\Models\DataKetidakhadiran;
use App\Models\DataSiswa;
use Illuminate\Support\Collection;

class AbsensiSyncService
{
    public function syncKelasSemester(int $kelasId, int $tahunId, string $semester): void
    {
        $siswaIds = DataSiswa::where('data_kelas_id', $kelasId)->pluck('id');

        foreach ($siswaIds as $siswaId) {
            [$sakit, $izin, $alpa] = $this->hitungRekapSiswaSemester((int) $siswaId, $tahunId, $semester);

            DataKetidakhadiran::updateOrCreate(
                [
                    'data_siswa_id' => (int) $siswaId,
                    'data_tahun_pelajaran_id' => $tahunId,
                    'semester' => $semester,
                ],
                [
                    'sakit' => $sakit,
                    'izin' => $izin,
                    'tanpa_keterangan' => $alpa,
                ]
            );
        }
    }

    public function hitungRekapSiswaSemester(int $siswaId, int $tahunId, string $semester): array
    {
        $records = AbsensiJamSiswa::query()
            ->where('data_siswa_id', $siswaId)
            ->where('data_tahun_pelajaran_id', $tahunId)
            ->where('semester', $semester)
            ->get();

        return $this->hitungRekapFromRecords($records);
    }

    public function hitungRekapFromRecords(Collection $records): array
    {
        $sakit = 0;
        $izin = 0;
        $alpa = 0;

        $byTanggal = $records->groupBy('tanggal');
        foreach ($byTanggal as $items) {
            $statuses = $items->pluck('status')->filter()->values();
            if ($statuses->isEmpty()) {
                continue;
            }

            // Jika ada hadir di salah satu jam, tidak dihitung tidak hadir harian.
            if ($statuses->contains('H')) {
                continue;
            }

            $countS = $statuses->filter(fn($s) => $s === 'S')->count();
            $countI = $statuses->filter(fn($s) => $s === 'I')->count();
            $countA = $statuses->filter(fn($s) => $s === 'A')->count();

            $max = max($countA, $countI, $countS);
            if ($max === 0) {
                continue;
            }

            if ($countA === $max) {
                $alpa++;
            } elseif ($countI === $max) {
                $izin++;
            } else {
                $sakit++;
            }
        }

        return [$sakit, $izin, $alpa];
    }
}

