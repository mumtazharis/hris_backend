<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bills extends Model
{
    protected $table = 'bills';
    protected $fillable = ['payment_id', 'user_id','plan_id', 'plan_name','total_employee','amount','period','deadline','pay_at','status','fine'];

   public function User()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function plan()
{
    return $this->belongsTo(BillingPlan::class, 'plan_id');
}
}
