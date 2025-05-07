<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckClock extends Model
{
    use HasFactory;
    protected $table = 'check_clocks';

    protected $fillable = [
        'employee_id',
        'check_clock_type',
        'check_clock_date',
        'check_clock_time',
        'latitude',
        'longitude',
        'evidence',
        'status',
    ];

    /**
     * Define the relationship with the Employee model.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
