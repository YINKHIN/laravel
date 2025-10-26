<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledReport extends Model
{
    protected $fillable = [
        'report_type',
        'report_name',
        'report_data',
        'report_period_start',
        'report_period_end',
        'generated_by'
    ];

    protected $casts = [
        'report_data' => 'array',
        'report_period_start' => 'datetime',
        'report_period_end' => 'datetime'
    ];
}