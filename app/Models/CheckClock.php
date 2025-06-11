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
        'submitter_id',
        'ck_setting_id',
        'position',
        'check_clock_date',
        'status',
        'status_approval',
        'reject_reason'
    ];

    /**
     * Define the relationship with the Employee model.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    public function checkClockSetting()
    {
        return $this->belongsTo(CheckClockSetting::class, 'ck_setting_id');
    }
    public function presentDetailCC()
    {
        return $this->hasMany(PresentDetail::class, 'ck_id');
    }
    public function submitter()
    {
        return $this->belongsTo(User::class);
    }
}
