<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Testing\Fluent\Concerns\Has;

class CheckClockSettingTimes extends Model
{
    use HasFactory;
    // Specify the table name
    protected $table = 'check_clock_setting_times';

    // Define the fillable properties
    protected $fillable = [
        'ck_setting_id',
        'day',
        'clock_in',
        'clock_out',
        'break_start',
        'break_end',
    ];

    // Define the relationship with the CheckClockSettings model
    public function checkClockSetting()
    {
        return $this->belongsTo(CheckClockSetting::class, 'ck_setting_id');
    }
}
