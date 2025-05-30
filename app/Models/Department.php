<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Testing\Fluent\Concerns\Has;

class Department extends Model
{
    use HasFactory;
    protected $table = 'departments';

    protected $fillable = [
        'company_id',
        'name',
        'description',
    ];

    protected $dates = [
        'deleted_at',
    ];

    public function position(){
        return $this->hasMany(Position::class);
    }

    public function company(){
        return $this->hasOne(Company::class, 'company_id', 'comapny_id');
    }
}
