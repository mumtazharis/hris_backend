<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class FormDataController extends Controller
{
    public function getDepartmentPosition(){
        $hrUser = Auth::user();
        $companyId = $hrUser->company_id;
        return DB::select("
            select  d.id as \"id_department\", d.name as \"Department\", p.id as \"id_position\", p.name as \"Position\"
            from departments d 
            join positions p on p.department_id = d.id
            WHERE d.company_id = ?
        ", [$companyId]);
    }

    public function getBank(){
        return DB::select("
            select * from banks
        ");
    }

    public function getEmployee(){
        $hrUser = Auth::user();
        $companyId = $hrUser->company_id;
        return DB::select("
            select e.employee_id, e.first_name || ' ' || e.last_name as full_name 
            from employees e
            where e.company_id = ? 
        ", [$companyId]);
    }
}
