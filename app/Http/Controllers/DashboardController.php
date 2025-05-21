<?php

namespace App\Http\Controllers;

use App\Models\CheckClock;
use App\Models\Employee;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{   
    public function dashboard()
    {
        return response()->json([
            'employeeCount' => $this->getEmployeeCount(),
            'approvalStatus' => $this->getApprovalStatus(),
            'attendancePercentage' => $this->getAttendance(),
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
            FROM employees;

        ");

    }
    public function getApprovalStatus()
    {
        return DB::select("
            select
                count(*) filter (where cc.status_approval = 'approved') as \"Approved\",
                count(*) filter (where cc.status_approval = 'waiting') as \"Waiting\",
                count(*) filter (where cc.status_approval = 'rejected') as \"Rejected\"
            from check_clocks cc
            where cc.check_clock_date::date = CURRENT_DATE
        ");
    }

    public function getAttendance(){
        return DB::select("
            select
                count(*) filter (where cc.status = 'present') as \"On Time\",
                count(*) filter (where cc.status = 'late') as \"Late\",
                count(*) filter (where cc.status = 'absent') as \"Absent\"
            from check_clocks cc
            where cc.check_clock_date::date = CURRENT_DATE
        ");
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
                FROM employees
            ) AS sub
        ");
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
        ");
    }

    public function getEmployeeWorkStatus(){
        return DB::select("
            select 
                COUNT(*) filter (where e.work_status = 'permanent') as \"Permanent\",
                COUNT(*) filter (where e.work_status = 'internship') as \"Internship\",
                COUNT(*) filter (where e.work_status = 'part-time') as \"Part-time\",
                COUNT(*) filter (where e.work_status = 'outsource') as \"Outsource\"
            from employees e 
        ");

    }

    public function getEmployeeGender(){
        return DB::select("
            select
                count(*) filter (where e.gender = 'male') as \"Male\",
                count(*) filter (where e.gender = 'female') as \"Female\"
            from employees e
            where e.employee_status = 'Active'
        ");
    }

    public function getEmployeeMaritalStatus(){
        return DB::select("
            select
                count(*) filter (where e.marital_status = 'single') as \"Single\",
                count(*) filter (where e.marital_status = 'married') as \"Married\",
                count(*) filter (where e.marital_status = 'divorced') as \"Divorced\",
                count(*) filter (where e.marital_status = 'widowed') as \"Widowed\"
            from employees e
            where e.employee_status = 'Active'
        ");
    }

    public function getEmployeeReligion(){
        return DB::select("
            select
                count(*) filter (where e.religion = 'Islam') as \"Islam\",
                count(*) filter (where e.religion = 'Hinduism') as \"Hinduism\",
                count(*) filter (where e.religion = 'Buddhism') as \"Buddhism\",
                count(*) filter (where e.religion = 'Christianity') as \"Christianity\",
                count(*) filter (where e.religion = 'Confucianism') as \"Confucianism\",
                count(*) filter (where e.religion = 'Other') as \"Other\"
            from employees e
            where e.employee_status = 'Active'
        ");
    }

    public function getEmployeeWorkYear(){
        return DB::select("
             SELECT
                COUNT(*) FILTER (WHERE year <=1) AS \"0-1\",
                COUNT(*) FILTER (WHERE year BETWEEN 2 AND 5) AS \"2-5\",
                COUNT(*) FILTER (WHERE year >= 6) AS \"6++\"
            FROM (
                SELECT DATE_PART('year', AGE(CURRENT_DATE, join_date)) AS year
                FROM employees
            ) AS sub
        ");
    }
}
