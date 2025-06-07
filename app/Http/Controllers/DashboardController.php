<?php

namespace App\Http\Controllers;

use App\Models\CheckClock;
use App\Models\Employee;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{   
    public function dashboard()
    {
        return response()->json([
            'employeeCount' => $this->getEmployeeCount(),
            'approvalStatus' => $this->getApprovalStatus(),
            'attendancePercentage' => $this->getAttendance(),
            // 'getOvertimeStatus' => $this->getOvertimeStatus(),
            'employeeAge' => $this->getEMployeeAge(),
            'lateEmployee' => $this->getLateEmployee(),
            'employeeWorkStatus' => $this->getEmployeeWorkStatus(),
            'employeeGender' => $this->getEmployeeGender(),
            'employeeMaritalStatus' => $this->getEmployeeMaritalStatus(),
            'employeeReligion' => $this->getEmployeeReligion(),
            'employeeWorkYear' => $this->getEmployeeWorkYear()
        ]);
    }
    public function getEmployeeCount(){
        return DB::select("
            SELECT
                COUNT(*) AS \"Total Employee\",
                COUNT(*) FILTER (
                    WHERE employee_status = 'Active'
                    AND join_date >= CURRENT_DATE - INTERVAL '30 days'
                ) AS \"New Employee\",
                COUNT(*) FILTER (
                    WHERE employee_status = 'Active'
                ) AS \"Active Employee\",
                COUNT(*) FILTER (
                    WHERE employee_status = 'Resigned'
                ) AS \"Resigned Employee\"
            FROM employees
            WHERE employees.company_id = ?

        ", [Auth::user()->company_id]);

    }
    public function getApprovalStatus()
    {
        return DB::select("
            select
                count(*) filter (where cc.status_approval = 'Approved') as \"Approved\",
                count(*) filter (where cc.status_approval = 'Pending') as \"Waiting\",
                count(*) filter (where cc.status_approval = 'Rejected') as \"Rejected\"
            from check_clocks cc
            join employees e on e.id = cc.employee_id
            where cc.check_clock_date::date = CURRENT_DATE
            AND e.company_id = ?
        ", [Auth::user()->company_id]);
    }

    public function getAttendance(){
        return DB::select("
            select
                count(*) filter (where cc.status = 'Present') as \"On Time\",
                count(*) filter (where cc.status = 'Late') as \"Late\",
                count(*) filter (where cc.status = 'Absent') as \"Absent\"
            from check_clocks cc
            join employees e on e.id = cc.employee_id
            where cc.check_clock_date::date = CURRENT_DATE
            and e.company_id = ?
        ", [Auth::user()->company_id]);
    }

    public function getOvertimeStatus()
    {
        return DB::select("
            select
                count(*) filter (where o.status_approval = 'Approved') as \"Approved\",
                count(*) filter (where o.status_approval = 'Waiting') as \"Waiting\",
                count(*) filter (where o.status_approval = 'Rejected') as \"Rejected\"
            from overtime o
            join employees e on e.id = o.employee_id
            where o.date::date = CURRENT_DATE
            and e.company_id = ?
        ", [Auth::user()->company_id]);
    }

    public function getEMployeeAge(){
        return DB::select("
            SELECT
                COUNT(*) FILTER (WHERE usia BETWEEN 21 AND 30) AS \"21-30\",
                COUNT(*) FILTER (WHERE usia BETWEEN 31 AND 40) AS \"31-40\",
                COUNT(*) FILTER (WHERE usia BETWEEN 41 AND 50) AS \"41-50\",
                COUNT(*) FILTER (WHERE usia >= 51) AS \"51++\"
            FROM (
                SELECT DATE_PART('year', AGE(CURRENT_DATE, birth_date)) AS usia
                FROM employees where employees.company_id = ?
            ) AS sub
        ", [Auth::user()->company_id]);
    }

    public function getLateEmployee(){
       return DB::select("
            select
                e.first_name || ' ' || e.last_name as \"Name\",
                to_char(cc.check_clock_date, 'HH24:MI') as \"Time\",
                p.name as \"Position\"
            from check_clocks cc
            join employees e on cc.employee_id = e.id
            join positions p on e.position_id = p.id
            where cc.status = 'present'
            and e.company_id = ?
        ", [Auth::user()->company_id]);
    }

    public function getEmployeeWorkStatus(){
        return DB::select("
            select 
                COUNT(*) filter (where e.contract_type = 'Permanent') as \"Permanent\",
                COUNT(*) filter (where e.contract_type = 'Internship') as \"Internship\",
                COUNT(*) filter (where e.contract_type = 'Contract') as \"Contract\"
            from employees e 
            where e.company_id = ?
        ", [Auth::user()->company_id]);

    }

    public function getEmployeeGender(){
        return DB::select("
            select
                count(*) filter (where e.gender = 'Male') as \"Male\",
                count(*) filter (where e.gender = 'Female') as \"Female\"
            from employees e
            where e.employee_status = 'Active'
            and e.company_id = ?
        ", [Auth::user()->company_id]);
    }

    public function getEmployeeMaritalStatus(){
        return DB::select("
            select
                count(*) filter (where e.marital_status = 'Single') as \"Single\",
                count(*) filter (where e.marital_status = 'Married') as \"Married\",
                count(*) filter (where e.marital_status = 'Divorced') as \"Divorced\",
                count(*) filter (where e.marital_status = 'Widowed') as \"Widowed\"
            from employees e
            where e.employee_status = 'Active'
            and e.company_id = ?
        ", [Auth::user()->company_id]);
    }

    public function getEmployeeReligion(){
        return DB::select("
            select
                count(*) filter (where e.religion = 'Islam') as \"Islam\",
                count(*) filter (where e.religion = 'Hinduism') as \"Hinduism\",
                count(*) filter (where e.religion = 'Buddhism') as \"Buddhism\",
                count(*) filter (where e.religion = 'Christian') as \"Christian\",
                count(*) filter (where e.religion = 'Confucianism') as \"Confucianism\",
                count(*) filter (where e.religion = 'Other') as \"Other\"
            from employees e
            where e.employee_status = 'Active'
            and e.company_id = ?
        ", [Auth::user()->company_id]);
    }

    public function getEmployeeWorkYear(){
        return DB::select("
             SELECT
                COUNT(*) FILTER (WHERE year <=1) AS \"0-1\",
                COUNT(*) FILTER (WHERE year BETWEEN 2 AND 5) AS \"2-5\",
                COUNT(*) FILTER (WHERE year >= 6) AS \"6++\"
            FROM (
                SELECT DATE_PART('year', AGE(CURRENT_DATE, join_date)) AS year
                FROM employees where employees.company_id = ?
            ) AS sub
        ", [Auth::user()->company_id]);
    }
}
