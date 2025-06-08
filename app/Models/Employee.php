<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    // use HasFactory;
    use SoftDeletes;
    
    protected $table = 'employees';
    protected $fillable = [
    'user_id',
    'ck_setting_id',
    'company_id',
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
    'education',
    'religion',
    'marital_status',
    'citizenship',
    'gender',
    'blood_type',
    'salary',
    'contract_type',
    'bank_code',
    'account_number',
    'contract_end',
    'join_date',
    'exit_date',
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

    public function bank(){
        return $this->belongsTo(Bank::class, 'bank_code', 'code');
    }

    public function document(){
        return $this->hasMany(Document::class, 'employee_id', 'employee_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }
}