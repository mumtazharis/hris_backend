<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OvertimeSetting extends Model
{
    use SoftDeletes;
    protected $table = 'overtime_settings';
    protected $fillable = [
        'company_id',
        'name',
        'type',
        'category',
        'working_days',
        'status'
    ];

    public function overtime(){
        return $this->hasMany(Overtime::class);
    }

    public function overtimeFormula(){
        return $this->hasMany(OvertimeFormula::class);
    }

       public function company(){
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }
}
