<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PresentDetail extends Model
{
    protected $table = 'present_detail_cc';

    protected $fillable = [
        'ck_id',
        'check_clock_type',
        'check_clock_time',
        'latitude',
        'longitude',
        'evidence',
    ];

    public function check_clock()
    {
        return $this->belongsTo(CheckClock::class);
    }
}
