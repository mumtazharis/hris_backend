<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'departments';

    protected $fillable = [
        'company_id',
        'name',
        'description',
    ];

    protected $dates = [
        'deleted_at',
    ];

    public function positions(){
        return $this->hasMany(Position::class);
    }

    public function company(){
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }

}
