<?php

namespace App\Support;

use Illuminate\Support\Collection;

class AbsensiCalculator
{
    public static function summarize(Collection $records): array
    {
        $counts = $records
            ->pluck('status')
            ->countBy();

        $hadir = (int) $counts->get('H', 0);
        $sakit = (int) $counts->get('S', 0);
        $izin = (int) $counts->get('I', 0);
        $alpa = (int) $counts->get('A', 0);
        $totalJam = $hadir + $sakit + $izin + $alpa;
        $skor = $hadir + (($sakit + $izin) * 0.5);

        return [
            'hadir' => $hadir,
            'sakit' => $sakit,
            'izin' => $izin,
            'alpa' => $alpa,
            'total_jam' => $totalJam,
            'skor' => $skor,
            'persentase' => $totalJam > 0 ? ($skor / $totalJam) * 100 : null,
        ];
    }
}
