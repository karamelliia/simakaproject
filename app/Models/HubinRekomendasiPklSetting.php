<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HubinRekomendasiPklSetting extends Model
{
    protected $table = 'hubin_rekomendasi_pkl_settings';

    protected $fillable = [
        'weights',
        'grade_thresholds',
        'attendance_default_score_without_data',
        'updated_by',
    ];

    protected $casts = [
        'weights' => 'array',
        'grade_thresholds' => 'array',
        'attendance_default_score_without_data' => 'float',
    ];
}
