<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckClockSetting extends Model
{
    use HasFactory;
    // Specify the table name
    protected $table = 'check_clock_settings';

    // Specify the fillable fields
    protected $fillable = [
        'company_id',
        'name',
        'latitude',
        'longitude',
        'radius',
    ];
    
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }

}
