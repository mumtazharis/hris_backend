<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckClockSetting extends Model
{
    // Specify the table name
    protected $table = 'check_clock_settings';

    // Specify the fillable fields
    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'radius',
    ];
}
