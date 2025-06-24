<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Overtime extends Model
{
    protected $table = 'overtime';
    protected $fillable = [
        'employee_id',
        'overtime_setting_id',
        'date',
        'start_hour',
        'end_hour',
        'payroll',
        'status',
        'evidence',
        'rejection_reason',
    ];
    
    public function employee(){
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
    public function overtimeSetting(){
        return $this->belongsTo(OvertimeSetting::class);
    }


}
