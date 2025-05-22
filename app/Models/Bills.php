<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bills extends Model
{
    protected $table = 'bills';
    protected $fillable = ['payment_id', 'user_id', 'total_employee','amount','period','deadline','status','created_at','updated_at','deleted_at'];

   public function User()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
