<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $table = 'companies';
    protected $fillable = ['name', 'company_id','plan_id'];

    public function user(){
        return $this->hasMany(User::class, 'company_id', 'company_id');
    }
    public function overtimeSetting(){
        return $this->hasMany(User::class, 'company_id', 'company_id');
    }

    public function department(){
        return $this->hasMany(Department::class, 'company_id', 'company_id');
    }
    public function billingPlan()
    {
        return $this->belongsTo(BillingPlan::class, 'plan_id', 'id');
    }
}
