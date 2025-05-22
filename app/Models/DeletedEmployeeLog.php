<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeletedEmployeeLog extends Model
{
    protected $table = 'deleted_employee_log';
    protected $fillable = [ 'user_id', 'deleted_employee', 'created_at', 'updated_at'];

     public function User()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
