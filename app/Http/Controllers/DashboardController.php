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
        $hrUser = Auth::user();
        $companyId = $hrUser->company_id;
        return response()->json([
            'employeeCount' => $this->getEmployeeCount($companyId),
            'approvalStatus' => $this->getApprovalStatus($companyId),
            'attendancePercentage' => $this->getAttendance($companyId),
            // 'getOvertimeStatus' => $this->getOvertimeStatus($companyId),
            'employeeAge' => $this->getEMployeeAge($companyId),
            'lateEmployee' => $this->getLateEmployee($companyId),
            'employeeWorkStatus' => $this->getEmployeeWorkStatus($companyId),
            'employeeGender' => $this->getEmployeeGender($companyId),
            'employeeMaritalStatus' => $this->getEmployeeMaritalStatus($companyId),
            'employeeReligion' => $this->getEmployeeReligion($companyId),
            'employeeWorkYear' => $this->getEmployeeWorkYear($companyId)
        ]);
    }
    public function getEmployeeCount(string $companyId){
        return DB::select("
            SELECT
                COUNT(*) AS \"Total Employee\",
                COUNT(*) FILTER (
                    WHERE e.employee_status = 'Active'
                    AND e.join_date >= CURRENT_DATE - INTERVAL '30 days'
                ) AS \"New Employee\",
                COUNT(*) FILTER (
                    WHERE e.employee_status = 'Active'
                ) AS \"Active Employee\",
                COUNT(*) FILTER (
                    WHERE e.employee_status = 'Resigned'
                ) AS \"Resigned Employee\"
            FROM employees e
            JOIN users u ON e.user_id = u.id
            WHERE e.company_id = ?
        ", [$companyId]);

    }
    public function getApprovalStatus(string $companyId)
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
        ",  [$companyId]);
    }

    public function getAttendance(string $companyId){
        return DB::select("
            select
                count(*) filter (where cc.status = 'Present') as \"On Time\",
                count(*) filter (where cc.status = 'Late') as \"Late\",
                count(*) filter (where cc.status = 'Absent') as \"Absent\"
            from check_clocks cc
            join employees e on e.id = cc.employee_id
            where cc.check_clock_date::date = CURRENT_DATE
            and e.company_id = ?
        ",  [$companyId]);
    }

    public function getOvertimeStatus(string $companyId)
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
        ",  [$companyId]);
    }

    public function getEMployeeAge(string $companyId){
        return DB::select("
            SELECT
                COUNT(*) FILTER (WHERE usia BETWEEN 21 AND 30) AS \"21-30\",
                COUNT(*) FILTER (WHERE usia BETWEEN 31 AND 40) AS \"31-40\",
                COUNT(*) FILTER (WHERE usia BETWEEN 41 AND 50) AS \"41-50\",
                COUNT(*) FILTER (WHERE usia >= 51) AS \"51++\"
            FROM (
                SELECT DATE_PART('year', AGE(CURRENT_DATE, e.birth_date)) AS usia
                FROM employees e
                 JOIN users u ON e.user_id = u.id 
                WHERE e.company_id = ?
            ) AS sub
        ", [$companyId]);
    }

    public function getLateEmployee(string $companyId){
       return DB::select("
            select
                e.first_name || ' ' || e.last_name as \"Name\",
                pdc.check_clock_time as \"Time\",
                p.name as \"Position\"
            from check_clocks cc
            join present_detail_cc pdc on pdc.ck_id = cc.id
            join employees e on cc.employee_id = e.id
            join positions p on e.position_id = p.id
            where cc.status = 'Present'
            and pdc.check_clock_type = 'in'
            and e.company_id = ?
        ",  [$companyId]);
    }

    public function getEmployeeWorkStatus(string $companyId){
        return DB::select("
            select 
                COUNT(*) filter (where e.contract_type = 'Permanent') as \"Permanent\",
                COUNT(*) filter (where e.contract_type = 'Internship') as \"Internship\",
                COUNT(*) filter (where e.contract_type = 'Contract') as \"Contract\"
            from employees e
            JOIN users u ON e.user_id = u.id
            WHERE e.company_id = ?
        ", [$companyId]);

    }

    public function getEmployeeGender(string $companyId){
        return DB::select("
            select
                count(*) filter (where e.gender = 'Male') as \"Male\",
                count(*) filter (where e.gender = 'Female') as \"Female\"
            from employees e
            JOIN users u ON e.user_id = u.id
            where e.employee_status = 'Active' AND e.company_id = ?
        ", [$companyId]);
    }

    public function getEmployeeMaritalStatus(string $companyId){
        return DB::select("
            select
                count(*) filter (where e.marital_status = 'Single') as \"Single\",
                count(*) filter (where e.marital_status = 'Married') as \"Married\",
                count(*) filter (where e.marital_status = 'Divorced') as \"Divorced\",
                count(*) filter (where e.marital_status = 'Widowed') as \"Widowed\"
            from employees e
            JOIN users u ON e.user_id = u.id
            where e.employee_status = 'Active' AND e.company_id = ?
        ", [$companyId]);
    }

    public function getEmployeeReligion(string $companyId){
        return DB::select("
            select
                count(*) filter (where e.religion = 'Islam') as \"Islam\",
                count(*) filter (where e.religion = 'Hindu') as \"Hindu\",
                count(*) filter (where e.religion = 'Buddhism') as \"Buddhism\",
                count(*) filter (where e.religion = 'Christian') as \"Christian\",
                count(*) filter (where e.religion = 'Confucianism') as \"Confucianism\",
                count(*) filter (where e.religion = 'Other') as \"Other\"
            from employees e
            JOIN users u ON e.user_id = u.id
            where e.employee_status = 'Active' AND e.company_id = ?
        ", [$companyId]);
    }

    public function getEmployeeWorkYear(string $companyId){
        return DB::select("
             SELECT
                COUNT(*) FILTER (WHERE year <=1) AS \"0-1\",
                COUNT(*) FILTER (WHERE year BETWEEN 2 AND 5) AS \"2-5\",
                COUNT(*) FILTER (WHERE year >= 6) AS \"6++\"
            FROM (
                SELECT DATE_PART('year', AGE(CURRENT_DATE, e.join_date)) AS year
                FROM employees e
                JOIN users u ON e.user_id = u.id 
                AND e.company_id = ?
            ) AS sub
        ", [$companyId]);
    }
}
