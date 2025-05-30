<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FormDataController extends Controller
{
    public function getDepartmentPosition(){
        return DB::select("
            select  d.id as \"id_department\", d.name as \"Department\", p.id as \"id_position\", p.name as \"Position\"
            from departments d 
            join positions p on p.department_id = d.id 
        ");
    }

    public function getBank(){
        return DB::select("
            select * from banks
        ");
    }
}
