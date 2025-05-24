<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Testing\Fluent\Concerns\Has;

class Employee extends Model
{
    use HasFactory;
    
    protected $table = 'employees';
    protected $fillable = [
    'user_id',
    'ck_setting_id',
    'employee_id',
    'nik',
    'first_name',
    'last_name',
    'position_id',
    'address',
    'email',
    'phone',
    'birth_place',
    'birth_date',
    'religion',
    'marital_status',
    'citizenship',
    'gender',
    'blood_type',
    'salary',
    'work_status',
    'join_date',
    'resign_date',
    'employee_photo',
    'employee_status',
];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function checkClockSetting()
    {
        return $this->belongsTo(CheckClockSetting::class, 'ck_setting_id');
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

}