<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes; 

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'full_name',
        'email',
        'phone',
        'password',
        'google_id',
        'role',
        'is_profile_complete',
        'auth_provider',
        'reset_token',
        'reset_token_expire',
        'avatar_url',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'reset_token_expire' => 'datetime',
    ];

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }

    // Accessor untuk mendapatkan nama depan/belakang.
    // Ini membantu jika Anda perlu mengakses first_name/last_name di bagian lain aplikasi,
    // tanpa perlu mengubah kode setiap kali.
    public function getFirstNameAttribute()
    {
        if ($this->full_name) {
            $parts = explode(' ', $this->full_name, 2);
            return $parts[0] ?? null;
        }
        return null;
    }

    public function getLastNameAttribute()
    {
        if ($this->full_name) {
            $parts = explode(' ', $this->full_name, 2);
            return $parts[1] ?? null;
        }
        return null;
    }
}

?>