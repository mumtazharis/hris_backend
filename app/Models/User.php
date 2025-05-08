<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = ['company_id','full_name','email', 'phone', 'password', 'google_id', 'role', 'is_profile_complete', 'reset_token', 'reset_token_expire'];
    protected $hidden = ['password', 'remember_token'];

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    
}