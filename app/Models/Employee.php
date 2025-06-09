<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Testing\Fluent\Concerns\Has;

class Employee extends Model
{
    // use HasFactory;
    
    protected $table = 'employees';
    protected $fillable = [
    'user_id',
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

}