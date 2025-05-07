<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Testing\Fluent\Concerns\Has;

class Position extends Model
{
    use HasFactory;
    protected $table = 'positions';

    protected $fillable = [
        'name',
        'department_id',
        'salary',
    ];

    protected $casts = [
        'salary' => 'decimal:2',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
