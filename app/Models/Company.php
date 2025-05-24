<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $table = 'companies';
    protected $fillable = ['name', 'company_id','plan_id'];

    public function user(){
        return $this->hasOne(User::class);
    }
     public function billing_plans()
    {
        return $this->belongsTo(BillingPlan::class);
    }
}
