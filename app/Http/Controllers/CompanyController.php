<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
{
    public function show(){
        $user = Auth::user();
        return DB::select("
            select 
                c.company_id, c.name
            from companies c where c.company_id = ?
        ", [$user->company_id]);
    }
}
