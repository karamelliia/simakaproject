<?php

use App\Services\RekomendasiPklService;
use Illuminate\Support\Collection;

function pklService(): RekomendasiPklService
{
    return new RekomendasiPklService([
        'weights' => ['kehadiran' => 50, 'sikap' => 30, 'bk' => 0],
        'sikap_scores' => [
            'sangat baik' => 100,
            'baik' => 85,
            'cukup' => 70,
            'perlu bimbingan' => 50,
        ],
        'bk_penalty_ranges' => [
            ['max' => 0, 'penalty' => 0],
            ['max' => 24, 'penalty' => 2],
            ['max' => 49, 'penalty' => 5],
            ['max' => 99, 'penalty' => 10],
            ['max' => 199, 'penalty' => 15],
            ['max' => 399, 'penalty' => 25],
            ['max' => 699, 'penalty' => 30],
            ['max' => 9999, 'penalty' => 40],
            ['max' => null, 'penalty' => 100],
        ],
        'bk_light_annual_cap' => 500,
        'grade_thresholds' => ['A' => 85, 'B' => 75, 'C' => 65, 'D' => 50],
        'grade_labels' => [
            'A' => 'Sangat Direkomendasikan',
            'B' => 'Direkomendasikan',
            'C' => 'Direkomendasikan dengan Catatan',
            'D' => 'Perlu Pertimbangan',
            'E' => 'Tidak Direkomendasikan',
        ],
    ]);
}

test('absensi dihitung per hari dan sakit izin bernilai setengah', function () {
    $records = new Collection([
        (object) ['tanggal' => '2026-01-05', 'status' => 'H'],
        (object) ['tanggal' => '2026-01-05', 'status' => 'A'],
        (object) ['tanggal' => '2026-01-06', 'status' => 'S'],
        (object) ['tanggal' => '2026-01-07', 'status' => 'I'],
        (object) ['tanggal' => '2026-01-08', 'status' => 'A'],
    ]);

    $summary = pklService()->summarizeAttendance($records, null, 100);

    expect($summary['counts'])->toBe(['H' => 1, 'S' => 1, 'I' => 1, 'A' => 1])
        ->and($summary['skor'])->toBe(50.0);
});

test('penalti bk dikurangkan dari nilai dasar dan membatasi grade', function () {
    $result = pklService()->calculate(
        ['counts' => ['H' => 100, 'S' => 0, 'I' => 0, 'A' => 0], 'persentase' => 100, 'skor' => 100],
        'Sangat Baik',
        250
    );

    expect($result['base_score'])->toBe(100.0)
        ->and($result['bk_penalty'])->toBe(25.0)
        ->and($result['final_score'])->toBe(75.0)
        ->and($result['grade'])->toBe('C');
});

test('pelanggaran terminal selalu menghasilkan nilai nol dan grade E', function () {
    $result = pklService()->calculate(
        ['counts' => ['H' => 100, 'S' => 0, 'I' => 0, 'A' => 0], 'persentase' => 100, 'skor' => 100],
        'Sangat Baik',
        10000,
        true,
        true
    );

    expect($result['final_score'])->toBe(0.0)
        ->and($result['grade'])->toBe('E')
        ->and($result['is_terminal'])->toBeTrue();
});

test('poin ringan dibatasi dan berkurang setelah tiga bulan tanpa pelanggaran', function () {
    $violations = collect([
        (object) [
            'data_tahun_pelajaran_id' => 1,
            'tanggal' => now()->subDays(100)->toDateString(),
            'poin' => 600,
            'kategori' => 'ringan',
            'is_terminal' => false,
        ],
    ]);

    $summary = pklService()->summarizeBkPoints($violations, 1);

    expect($summary['historical_points'])->toBe(600)
        ->and($summary['active_points'])->toBe(250);
});

test('pelanggaran absensi tetap historis tetapi tidak mengurangi rekomendasi dua kali', function () {
    $violations = collect([
        (object) [
            'data_tahun_pelajaran_id' => 1,
            'tanggal' => now()->toDateString(),
            'poin' => 50,
            'kategori' => 'ringan',
            'is_terminal' => false,
            'affects_pkl_score' => false,
        ],
    ]);

    $summary = pklService()->summarizeBkPoints($violations, 1);

    expect($summary['historical_points'])->toBe(50)
        ->and($summary['active_points'])->toBe(0);
});

test('data kehadiran atau sikap yang kosong tidak diberi nilai default', function () {
    $result = pklService()->calculate(
        ['counts' => ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0], 'persentase' => null, 'skor' => null],
        '',
        0
    );

    expect($result['attendance_score'])->toBeNull()
        ->and($result['attitude_score'])->toBeNull()
        ->and($result['base_score'])->toBeNull()
        ->and($result['final_score'])->toBeNull()
        ->and($result['grade'])->toBeNull()
        ->and($result['label'])->toBe('Belum dapat dihitung');
});
