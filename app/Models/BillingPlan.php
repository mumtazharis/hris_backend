<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingPlan extends Model
{
    protected $table = 'billing_plans';
    protected $fillable = ['plan_name' ];

    public function user(){
        return $this->hasOne(User::class);
    }
    public function billingPrice(){
        return $this->hasMany(PlansPrice::class);
    }
}
