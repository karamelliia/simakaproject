<?php

use App\Support\AbsensiCalculator;
use Illuminate\Support\Collection;

test('menghitung rekap dan skor absensi per jam pelajaran', function () {
    $records = new Collection([
        (object) ['status' => 'H'],
        (object) ['status' => 'H'],
        (object) ['status' => 'S'],
        (object) ['status' => 'I'],
        (object) ['status' => 'A'],
    ]);

    $summary = AbsensiCalculator::summarize($records);

    expect($summary['hadir'])->toBe(2)
        ->and($summary['sakit'])->toBe(1)
        ->and($summary['izin'])->toBe(1)
        ->and($summary['alpa'])->toBe(1)
        ->and($summary['total_jam'])->toBe(5)
        ->and($summary['skor'])->toBe(3.0)
        ->and($summary['persentase'])->toBe(60.0);
});

test('mengembalikan persentase kosong ketika belum ada absensi', function () {
    $summary = AbsensiCalculator::summarize(collect());

    expect($summary['total_jam'])->toBe(0)
        ->and($summary['skor'])->toBe(0.0)
        ->and($summary['persentase'])->toBeNull();
});
