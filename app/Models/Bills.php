<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bills extends Model
{
    protected $table = 'bills';
    protected $fillable = ['payment_id', 'user_id', 'total_employee','amount','period','deadline','pay_at','status'];

   public function User()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
