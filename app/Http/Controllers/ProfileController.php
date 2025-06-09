<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function show(){
        $user = Auth::user();
        return DB::select("
            select 
                u.full_name,
                u.phone,
                u.email
            from users u where u.id = ?
        ", [$user->id]);
    }

    public function getCompanyDepPos(){
        $hrUser = Auth::user();
        $companyId = $hrUser->company_id;
        return DB::select("
            select  d.id as \"id_department\", d.name as \"Department\", p.id as \"id_position\", p.name as \"Position\"
            from departments d 
            join positions p on p.department_id = d.id
            WHERE d.company_id = ?
            ORDER BY d.id
        ", [$companyId]);
    }
}
