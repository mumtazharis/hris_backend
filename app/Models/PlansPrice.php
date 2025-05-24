<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlansPrice extends Model
{
    protected $table = 'plans_price';
    protected $fillable = [ 'plan_id','employee_min', 'employee_max', 'price', ];
}
