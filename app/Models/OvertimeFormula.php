<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OvertimeFormula extends Model
{
    protected $table = 'overtime_formula';
    protected $fillable = [
        'setting_id',
        'hour_start',
        'hour_end',
        'interval_hours',
        'formula'
    ];

    public function overtimeSetting(){
        return $this->belongsTo(OvertimeSetting::class);
    }

 
}
