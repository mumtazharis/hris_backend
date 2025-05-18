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
        'approver_id',
        'check_clock_date',
        'status',
        'status_approval',
    ];

    /**
     * Define the relationship with the Employee model.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    public function approver()
    {
        return $this->belongsTo(User::class);
    }
}
