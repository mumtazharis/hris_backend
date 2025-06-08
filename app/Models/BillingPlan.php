<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingPlan extends Model
{
    protected $table = 'billing_plans';
    protected $fillable = ['plan_name'];

    public function company(){
        return $this->hasOne(Company::class);
    }
    public function billingPrice(){
        return $this->hasMany(PlansPrice::class);
    }
}
