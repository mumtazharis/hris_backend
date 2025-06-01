<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeletedEmployeeLog extends Model
{
    protected $table = 'deleted_employee_log';
    protected $fillable = [ 'admin_id', 'deleted_employee_name'];

     public function User()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
