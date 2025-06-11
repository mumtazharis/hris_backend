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
        'total_hour',
        'payroll',
        'status',
        'rejection_reason',
    ];
    
    public function employee(){
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
    public function overtimeSetting(){
        return $this->belongsTo(OvertimeSetting::class);
    }


}
