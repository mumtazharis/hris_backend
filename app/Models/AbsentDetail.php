<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbsentDetail extends Model
{
    protected $table = 'absent_detail_cc';

    protected $fillable = [
        'ck_id',
        'start_date',
        'end_date',
        'evidence',
    ];

    public function check_clock()
    {
        return $this->belongsTo(CheckClock::class);
    }
}
