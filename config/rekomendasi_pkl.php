<?php

return [
    'weights' => [
        'kehadiran' => 50,
        'sikap' => 30,
        'bk' => 0,
    ],

    'sikap_scores' => [
        'sangat baik' => 100,
        'baik' => 85,
        'cukup' => 70,
        'perlu bimbingan' => 55,
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

    'grade_thresholds' => [
        'A' => 85,
        'B' => 75,
        'C' => 65,
        'D' => 50,
    ],

    'grade_labels' => [
        'A' => 'Sangat Direkomendasikan',
        'B' => 'Direkomendasikan',
        'C' => 'Direkomendasikan dengan Catatan',
        'D' => 'Perlu Pertimbangan',
        'E' => 'Tidak Direkomendasikan',
    ],

    'roles' => [
        'guru_global_access' => ['pembimbing_pkl'],
        'guru_limited_access' => ['wali_kelas'],
    ],
];
