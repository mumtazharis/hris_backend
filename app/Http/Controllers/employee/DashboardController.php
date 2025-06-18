<?php

namespace App\Http\Controllers\employee;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function dashboard(Request $request){
        $employeeId = Auth::user()->employee->id;
        $month = $request->query('month', date('m')); 
        $year = $request->query('year', date('Y'));  
        return response()->json([
            'totalWorkHour' => $this->getTotalWorkHour($employeeId, $month, $year),
            'totalAttendance' => $this->getTotalAttendance($employeeId, $month, $year),
            'leaveSummary' => $this->getLeaveSummary($employeeId, $year),
            'totalOnTime' => $this->getTotalOnTime($employeeId, $month, $year),
            'overtimeSummary' => $this->overtimeSummary($employeeId),
            'monthlySalaryLastYear' => $this->monthlySalaryLastYear($employeeId),
           
        ]);
    }

    public function getTotalWorkHour($employeeId, $month, $year){
        return DB::select("
            SELECT
                cc.employee_id,
                cc.check_clock_date AS date,
                ROUND(
                    EXTRACT(EPOCH FROM (
                        MAX(CASE WHEN pdc.check_clock_type = 'out' THEN pdc.check_clock_time END) -
                        MIN(CASE WHEN pdc.check_clock_type = 'in' THEN pdc.check_clock_time END)
                    )) / 3600,
                    2
                ) AS total_hour
            FROM
                check_clocks cc
            JOIN
                present_detail_cc pdc ON pdc.ck_id = cc.id
            WHERE
                cc.employee_id = ?
                AND EXTRACT(MONTH FROM cc.check_clock_date) = ?
                AND EXTRACT(YEAR FROM cc.check_clock_date) = ?
                AND cc.status_approval = 'Approved'
            GROUP BY
                cc.employee_id,
                cc.check_clock_date
            HAVING
                MIN(CASE WHEN pdc.check_clock_type = 'in' THEN pdc.check_clock_time END) IS NOT NULL AND
                MAX(CASE WHEN pdc.check_clock_type = 'out' THEN pdc.check_clock_time END) IS NOT NULL;

        ", [$employeeId, $month, $year]);
    }

    public function getTotalAttendance($employeeId, $month, $year){
        return DB::select("
            SELECT cc.status, COUNT(*)
            FROM check_clocks cc
            WHERE cc.employee_id = ?
            AND EXTRACT(MONTH FROM cc.check_clock_date) = ?
            AND EXTRACT(YEAR FROM cc.check_clock_date) = ?
            AND cc.status_approval = 'Approved'
            GROUP BY cc.status;
        ", [$employeeId, $month, $year]);
    }

    public function getLeaveSummary($employeeId, $year){
        $total_annual_leave = DB::select("
            SELECT COUNT(*) as total_annual_leave
            FROM check_clocks cc
            WHERE employee_id = ?
            AND EXTRACT(YEAR FROM cc.check_clock_date) = ?
            AND cc.status = 'Annual Leave'
            AND cc.status_approval = 'Approved';
        ", [$employeeId, $year]);
        $max_annual_leave = DB::select("
            select c.max_annual_leave 
            from companies c 
            join employees e on e.company_id = c.company_id
            where e.id = ?
        ", [$employeeId]);

        $total = $total_annual_leave[0]->total_annual_leave ?? 0;
        $max = $max_annual_leave[0]->max_annual_leave ?? 0;
        $remaining = $max - $total;

        return [
            'year' => $year,
            'used_annual_leave' => $total,
            'max_annual_leave' => $max,
            'remaining' => $remaining,
        ];

    }

    public function getTotalOnTime($employeeId, $month, $year){
        return DB::select("
            SELECT
                COUNT(CASE
                        WHEN pdc.check_clock_time BETWEEN ccst.min_clock_in AND ccst.clock_in
                        THEN 1
                    END) AS total_ontime,

                COUNT(CASE
                        WHEN pdc.check_clock_time > ccst.clock_in
                            AND pdc.check_clock_time <= ccst.max_clock_in
                        THEN 1
                    END) AS total_late
            FROM check_clock_setting_times ccst
            JOIN check_clock_settings ccs ON ccst.ck_setting_id = ccs.id
            JOIN check_clocks cc ON cc.ck_setting_id = ccst.id
            JOIN present_detail_cc pdc ON pdc.ck_id = cc.id
            WHERE pdc.check_clock_type = 'in'
            AND cc.employee_id = ?
            AND EXTRACT(MONTH FROM cc.check_clock_date) = ?
            AND EXTRACT(YEAR FROM cc.check_clock_date) = ?
            AND cc.status_approval = 'Approved'
        ", [$employeeId, $month, $year]);
    }

    public function monthlySalaryLastYear($employeeId) {
        return DB::select("
            WITH months AS (
                SELECT 
                    generate_series(
                        date_trunc('month', CURRENT_DATE) - INTERVAL '11 months',
                        date_trunc('month', CURRENT_DATE),
                        INTERVAL '1 month'
                    ) AS month
            )
            SELECT 
                m.month,
                CAST(e.salary AS BIGINT) AS salary,
                COALESCE(SUM(o.payroll), 0) AS total_overtime_pay,
                (CAST(e.salary AS BIGINT) + COALESCE(SUM(o.payroll), 0)) AS total_salary_with_overtime
            FROM 
                months m
            CROSS JOIN 
                employees e
            LEFT JOIN 
                overtime o ON o.employee_id = e.employee_id 
                    AND DATE_TRUNC('month', o.date) = m.month
            WHERE 
                e.id = ?
            GROUP BY 
                m.month, e.salary
            ORDER BY 
                m.month ASC
        ", [$employeeId]);
    }

    public function overtimeSummary($employeeId){
        return DB::select("
            SELECT 
                e.employee_id AS employee_id,
                FLOOR(COALESCE(SUM(EXTRACT(EPOCH FROM (o.end_hour - o.start_hour)) / 3600), 0)) AS total_overtime_this_week,
                c.max_weekly_overtime,
                FLOOR(c.max_weekly_overtime - COALESCE(SUM(EXTRACT(EPOCH FROM (o.end_hour - o.start_hour)) / 3600), 0)) AS remaining_overtime_hour
            FROM employees e
            JOIN companies c ON e.company_id = c.company_id
            LEFT JOIN overtime o ON o.employee_id = e.employee_id 
                AND o.date >= date_trunc('week', CURRENT_DATE)
                AND o.date < date_trunc('week', CURRENT_DATE) + INTERVAL '7 days'
            WHERE e.id = ?
            GROUP BY e.id, c.max_weekly_overtime
        ", [$employeeId]);
    }
}
