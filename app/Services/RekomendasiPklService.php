<?php

namespace App\Services;

use App\Models\HubinRekomendasiPklSetting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class RekomendasiPklService
{
    private ?array $runtimeConfigCache = null;

    public function __construct(private readonly ?array $configOverride = null)
    {
    }

    public function summarizeAttendance(Collection $records, $rekap, int $estimatedDays): array
    {
        $daily = ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0];

        foreach ($records->groupBy(fn($record) => (string) $record->tanggal) as $items) {
            $statuses = $items->pluck('status')->filter()->values();
            if ($statuses->isEmpty()) {
                continue;
            }

            if ($statuses->contains('H')) {
                $daily['H']++;
                continue;
            }

            $counts = [
                'A' => $statuses->filter(fn($status) => $status === 'A')->count(),
                'I' => $statuses->filter(fn($status) => $status === 'I')->count(),
                'S' => $statuses->filter(fn($status) => $status === 'S')->count(),
            ];
            $max = max($counts);

            if ($max <= 0) {
                continue;
            }

            // Prioritas A > I > S saat jumlah status sama.
            foreach (['A', 'I', 'S'] as $status) {
                if ($counts[$status] === $max) {
                    $daily[$status]++;
                    break;
                }
            }
        }

        if (array_sum($daily) === 0 && $rekap) {
            $tidakHadir = max(0, (int) $rekap->sakit)
                + max(0, (int) $rekap->izin)
                + max(0, (int) $rekap->tanpa_keterangan);
            $total = max($estimatedDays, $tidakHadir);
            $daily = [
                'H' => max($total - $tidakHadir, 0),
                'S' => max(0, (int) $rekap->sakit),
                'I' => max(0, (int) $rekap->izin),
                'A' => max(0, (int) $rekap->tanpa_keterangan),
            ];
        }

        $total = array_sum($daily);
        if ($total <= 0) {
            return [
                'counts' => $daily,
                'persentase' => null,
                'skor' => null,
            ];
        }

        $weightedPresence = $daily['H'] + (0.5 * ($daily['S'] + $daily['I']));
        $score = round(($weightedPresence / $total) * 100, 2);

        return [
            'counts' => $daily,
            'persentase' => $score,
            'skor' => $score,
        ];
    }

    public function calculate(
        array $attendance,
        string $predikat,
        int $activeBkPoints,
        bool $hasHeavyViolation = false,
        bool $hasTerminalViolation = false
    ): array {
        $attendanceScore = isset($attendance['skor']) ? (float) $attendance['skor'] : null;
        $attitudeScore = $this->scoreAttitude($predikat);

        if ($attendanceScore === null || $attitudeScore === null) {
            return $this->incompleteResult(
                $attendance,
                $attendanceScore,
                $attitudeScore,
                $activeBkPoints,
                $hasTerminalViolation
            );
        }

        $weights = $this->weights();

        $baseScore = round(
            ($attendanceScore * $weights['kehadiran'])
            + ($attitudeScore * $weights['sikap']),
            2
        );

        if ($hasTerminalViolation) {
            return $this->result(
                $attendance,
                $attendanceScore,
                $attitudeScore,
                10000,
                $baseScore,
                100,
                0,
                'E',
                'Pelanggaran sangat berat (terminal)'
            );
        }

        $penalty = $this->bkPenalty($activeBkPoints);
        $finalScore = max(0, min(100, round($baseScore - $penalty, 2)));
        $grade = $this->gradeFromScore($finalScore);
        $caps = [];

        $alpha = (int) ($attendance['counts']['A'] ?? 0);
        if ($alpha > 10) {
            $grade = $this->capGrade($grade, 'D');
            $caps[] = 'Alfa lebih dari 10 hari';
        } elseif ($alpha >= 6) {
            $grade = $this->capGrade($grade, 'C');
            $caps[] = 'Alfa 6-10 hari';
        }

        if ($activeBkPoints >= 700) {
            $grade = $this->capGrade($grade, 'E');
            $caps[] = 'Poin BK aktif minimal 700';
        } elseif ($activeBkPoints >= 400 || $hasHeavyViolation) {
            $grade = $this->capGrade($grade, 'D');
            $caps[] = $hasHeavyViolation ? 'Memiliki pelanggaran berat aktif' : 'Poin BK aktif minimal 400';
        } elseif ($activeBkPoints >= 200) {
            $grade = $this->capGrade($grade, 'C');
            $caps[] = 'Poin BK aktif minimal 200';
        }

        return $this->result(
            $attendance,
            $attendanceScore,
            $attitudeScore,
            $activeBkPoints,
            $baseScore,
            $penalty,
            $finalScore,
            $grade,
            implode('; ', array_unique($caps))
        );
    }

    public function summarizeBkPoints(Collection $violations, ?int $activeSchoolYearId): array
    {
        $scoredViolations = $violations->filter(
            fn($row) => !property_exists($row, 'affects_pkl_score') || (bool) $row->affects_pkl_score
        );

        $terminal = $scoredViolations->contains(fn($row) => (bool) ($row->is_terminal ?? false)
            || ($row->kategori ?? null) === 'sangat_berat');

        if ($terminal) {
            return [
                'historical_points' => (int) $violations->sum('poin'),
                'active_points' => 10000,
                'has_heavy' => true,
                'has_terminal' => true,
            ];
        }

        $heavy = $scoredViolations->filter(fn($row) => ($row->kategori ?? null) === 'berat');
        $current = $scoredViolations->filter(
            fn($row) => !$activeSchoolYearId || (int) $row->data_tahun_pelajaran_id === $activeSchoolYearId
        );
        $light = $current->filter(fn($row) => ($row->kategori ?? 'ringan') === 'ringan');
        $medium = $current->filter(fn($row) => ($row->kategori ?? null) === 'sedang');

        $lightPoints = min(
            (int) ($this->runtimeConfig()['bk_light_annual_cap'] ?? 500),
            (int) $light->sum('poin')
        );

        $latestViolationDate = $current->max('tanggal');
        if (
            $latestViolationDate
            && CarbonImmutable::parse($latestViolationDate)->startOfDay()->lte(now()->startOfDay()->subDays(90))
        ) {
            $lightPoints = (int) floor($lightPoints * 0.5);
        }

        return [
            'historical_points' => (int) $violations->sum('poin'),
            'active_points' => $lightPoints + (int) $medium->sum('poin') + (int) $heavy->sum('poin'),
            'has_heavy' => $heavy->isNotEmpty(),
            'has_terminal' => false,
        ];
    }

    public function weights(): array
    {
        $raw = $this->runtimeConfig()['weights'] ?? [];
        $attendance = max(0, (float) ($raw['kehadiran'] ?? 50));
        $attitude = max(0, (float) ($raw['sikap'] ?? 30));
        $total = $attendance + $attitude;

        if ($total <= 0) {
            return [
                'kehadiran' => 0.625,
                'sikap' => 0.375,
                'raw' => [62.5, 37.5],
            ];
        }

        return [
            'kehadiran' => $attendance / $total,
            'sikap' => $attitude / $total,
            'raw' => [($attendance / $total) * 100, ($attitude / $total) * 100],
        ];
    }

    public function gradeThresholds(): array
    {
        return $this->runtimeConfig()['grade_thresholds'] ?? [
            'A' => 85,
            'B' => 75,
            'C' => 65,
            'D' => 50,
        ];
    }

    public function runtimeConfig(): array
    {
        if ($this->configOverride !== null) {
            return $this->configOverride;
        }

        if ($this->runtimeConfigCache !== null) {
            return $this->runtimeConfigCache;
        }

        $base = config('rekomendasi_pkl', []);

        if (!Schema::hasTable('hubin_rekomendasi_pkl_settings')) {
            return $this->runtimeConfigCache = $base;
        }

        $row = HubinRekomendasiPklSetting::query()->first();
        if (!$row) {
            return $this->runtimeConfigCache = $base;
        }

        if (is_array($row->weights)) {
            $base['weights'] = array_merge($base['weights'] ?? [], $row->weights);
        }
        if (is_array($row->grade_thresholds)) {
            $base['grade_thresholds'] = array_merge($base['grade_thresholds'] ?? [], $row->grade_thresholds);
        }
        if ($row->attendance_default_score_without_data !== null) {
            $base['attendance_default_score_without_data'] = (float) $row->attendance_default_score_without_data;
        }

        return $this->runtimeConfigCache = $base;
    }

    public function clearConfigCache(): void
    {
        $this->runtimeConfigCache = null;
    }

    private function scoreAttitude(string $predikat): ?float
    {
        $cfg = $this->runtimeConfig();
        $key = strtolower(trim($predikat));

        if ($key === '' || $key === '-' || !array_key_exists($key, $cfg['sikap_scores'] ?? [])) {
            return null;
        }

        return (float) $cfg['sikap_scores'][$key];
    }

    private function bkPenalty(int $points): float
    {
        foreach (($this->runtimeConfig()['bk_penalty_ranges'] ?? []) as $range) {
            $max = $range['max'] ?? null;
            if ($max === null || $points <= (int) $max) {
                return (float) ($range['penalty'] ?? 40);
            }
        }

        return 40;
    }

    private function gradeFromScore(float $score): string
    {
        foreach (['A', 'B', 'C', 'D'] as $grade) {
            if ($score >= (float) ($this->gradeThresholds()[$grade] ?? 0)) {
                return $grade;
            }
        }

        return 'E';
    }

    private function capGrade(string $grade, string $maximumGrade): string
    {
        $rank = ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5];

        return ($rank[$grade] ?? 5) < ($rank[$maximumGrade] ?? 5) ? $maximumGrade : $grade;
    }

    private function result(
        array $attendance,
        float $attendanceScore,
        float $attitudeScore,
        int $activeBkPoints,
        float $baseScore,
        float $penalty,
        float $finalScore,
        string $grade,
        string $capReason
    ): array {
        $labels = $this->runtimeConfig()['grade_labels'] ?? [];

        return [
            'attendance_counts' => $attendance['counts'] ?? ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0],
            'attendance_percentage' => $attendance['persentase'] ?? null,
            'attendance_score' => round($attendanceScore, 2),
            'attitude_score' => round($attitudeScore, 2),
            'active_bk_points' => $activeBkPoints,
            'base_score' => round($baseScore, 2),
            'bk_penalty' => round($penalty, 2),
            'final_score' => round($finalScore, 2),
            'grade' => $grade,
            'label' => $labels[$grade] ?? 'Tidak Direkomendasikan',
            'cap_reason' => $capReason,
            'is_terminal' => $activeBkPoints >= 10000,
            'is_complete' => true,
        ];
    }

    private function incompleteResult(
        array $attendance,
        ?float $attendanceScore,
        ?float $attitudeScore,
        int $activeBkPoints,
        bool $hasTerminalViolation
    ): array {
        $missing = [];
        if ($attendanceScore === null) {
            $missing[] = 'data kehadiran';
        }
        if ($attitudeScore === null) {
            $missing[] = 'data sikap';
        }

        if ($hasTerminalViolation) {
            return [
                'attendance_counts' => $attendance['counts'] ?? ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0],
                'attendance_percentage' => $attendance['persentase'] ?? null,
                'attendance_score' => $attendanceScore,
                'attitude_score' => $attitudeScore,
                'active_bk_points' => 10000,
                'base_score' => null,
                'bk_penalty' => 100.0,
                'final_score' => 0.0,
                'grade' => 'E',
                'label' => 'Tidak Direkomendasikan',
                'cap_reason' => 'Pelanggaran sangat berat (terminal)',
                'is_terminal' => true,
                'is_complete' => true,
            ];
        }

        return [
            'attendance_counts' => $attendance['counts'] ?? ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0],
            'attendance_percentage' => $attendance['persentase'] ?? null,
            'attendance_score' => $attendanceScore,
            'attitude_score' => $attitudeScore,
            'active_bk_points' => $activeBkPoints,
            'base_score' => null,
            'bk_penalty' => $this->bkPenalty($activeBkPoints),
            'final_score' => null,
            'grade' => null,
            'label' => 'Belum dapat dihitung',
            'cap_reason' => 'Lengkapi ' . implode(' dan ', $missing),
            'is_terminal' => false,
            'is_complete' => false,
        ];
    }
}
