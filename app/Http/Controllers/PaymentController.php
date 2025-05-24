<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Product;
use App\Models\Bills;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

use Inertia\Inertia;


class PaymentController extends Controller
{
    public function index()
    {
        $products = Bills::with('payments')->get();

        $products->map(function ($product) {
            $product->payment_url = $product->payments->count() > 0 ? $product->payments[0]->payment_url : null;
            return $product;
        });

        // return Inertia::render('Product/Index', [
        //     'products' => $products,
        // ]);
    }

    public function getOrderSummary(): JsonResponse
    {
        $loggedInUserId = 1;
        // if (!Auth::check()) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'User not authenticated.'
        //     ], 401);
        // }

        // $loggedInUserId = Auth::id();

        // Dapatkan informasi plan
        $plan = "
                SELECT
                    bp.plan_name AS plan_name,
                    bp.id AS plan_id
                FROM
                    users u
                JOIN
                    companies c ON u.company_id = c.id
                JOIN
                    billing_plans bp ON c.plan_id = bp.id
                WHERE
                    u.id = 1;
                ";

        if (!$plan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Plan information not found.'
            ], 404);
        }

        // Hitung total karyawan
    //    $totalEmployeeQuery = (
    //         "SELECT
    //                     (SELECT COUNT(*) FROM employees) +
    //                     (SELECT COUNT(*) FROM deleted_employee_log 
    //                 WHERE TO_CHAR(created_at, 'YYYY-MM') = TO_CHAR(CURRENT_TIMESTAMP, 'YYYY-MM'))
    //                 AS total_employees_including_this_month_deleted");
        $totalEmployee =("
    SELECT
        (SELECT COUNT(*) FROM employees) +
        (SELECT COUNT(*) FROM deleted_employee_log
        WHERE TO_CHAR(created_at, 'YYYY-MM') = TO_CHAR(CURRENT_TIMESTAMP, 'YYYY-MM'))
    AS total_employees
");
// $numberOfEmployees = $totalEmployeeResult->total_employees ?? 0;
        // $totalEmployeeResult = DB::selectOne($totalEmployeeQuery, [$loggedInUserId, $loggedInUserId]);
        // $numberOfEmployees = $totalEmployeeResult->total_employees ?? 0;

        // Dapatkan harga per user berdasarkan jumlah karyawan
        $price = DB::selectOne(
            "SELECT
                        pp.price
                    FROM
                        billing_plans bp
                    JOIN
                        plans_price pp ON bp.id = pp.plan_id
                    JOIN
                        companies c ON c.plan_id = bp.id
                    JOIN
                        users u ON u.company_id = c.id
                    WHERE
                        u.id = 1 AND
                        (
                            (SELECT COUNT(*) FROM employees) +
                            (SELECT COUNT(*)
                            FROM deleted_employee_log
                            WHERE TO_CHAR(created_at, 'YYYY-MM') = TO_CHAR(CURRENT_TIMESTAMP, 'YYYY-MM'))
                        ) >= pp.employee_min AND
                        (pp.employee_max IS NULL OR
                        (
                            (SELECT COUNT(*) FROM employees) +
                            (SELECT COUNT(*)
                            FROM deleted_employee_log
                            WHERE TO_CHAR(created_at, 'YYYY-MM') = TO_CHAR(CURRENT_TIMESTAMP, 'YYYY-MM'))
                        ) <= pp.employee_max
                        );");
        // $pricePerUserResult = DB::selectOne($price, [$plan, $numberOfEmployees, $numberOfEmployees]);
        // $pricePerUser = $pricePerUserResult->price ?? 0;

        // Hitung subtotal
        $subtotal = $totalEmployee * $price;

        return response()->json([
            'status' => 'success',
            'data' => [
                'package' => $plan,
                'numberOfEmployees' => $totalEmployee,
                'pricePerUser' => $price,
                'subtotal' => $subtotal,
                // Tambahkan data lain yang diperlukan
            ]
        ]);
    }

    public function createInvoice(Request $request)
    {
        $api_key = base64_encode(env('XENDIT_SECRET_KEY'));
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . $api_key,
        ];
        $totalEmployee = DB::selectOne(
            "SELECT
                        (SELECT COUNT(*) FROM employees) +
                        (SELECT COUNT(*) FROM deleted_employee_log 
                    WHERE TO_CHAR(created_at, 'YYYY-MM') = TO_CHAR(CURRENT_TIMESTAMP, 'YYYY-MM'))
                    AS total_employees_including_this_month_deleted")->total_employees_including_this_month_deleted;

        $price = DB::selectOne(
            "SELECT
                        pp.price
                    FROM
                        billing_plans bp
                    JOIN
                        plans_price pp ON bp.id = pp.plan_id
                    JOIN
                        companies c ON c.plan_id = bp.id
                    JOIN
                        users u ON u.company_id = c.id
                    WHERE
                        u.id = 1 AND
                        (
                            (SELECT COUNT(*) FROM employees) +
                            (SELECT COUNT(*)
                            FROM deleted_employee_log
                            WHERE TO_CHAR(created_at, 'YYYY-MM') = TO_CHAR(CURRENT_TIMESTAMP, 'YYYY-MM'))
                        ) >= pp.employee_min AND
                        (pp.employee_max IS NULL OR
                        (
                            (SELECT COUNT(*) FROM employees) +
                            (SELECT COUNT(*)
                            FROM deleted_employee_log
                            WHERE TO_CHAR(created_at, 'YYYY-MM') = TO_CHAR(CURRENT_TIMESTAMP, 'YYYY-MM'))
                        ) <= pp.employee_max
                        );");

        $res = Http::withHeaders($headers)->post('https://api.xendit.co/v2/invoices', [
            'external_id' => $request['external_id'],
            'total_employee'=> $totalEmployee,
            'amount' => ($totalEmployee * $price->price),
            'invoice_duration' => $request['invoice_duration'],
        ]);

        return json_decode($res->body(), true);
    }

    public function createPayment($id)
    {
        $product = Bills::find($id);
        $isAlreadyExist = Bills::where('product_id', $id)
            ->where('status', 'pending')
            ->exists();

        if ($isAlreadyExist) {
            return response()->json([
                'status' => 'error',
                'message' => 'You already have a pending payment for this product'
            ], 400);
        }
        $externalId = 'INV-' . date('Ymd') . '-' . rand(100, 999);

        $payment = $product->payments()->create([
            'external_id' => $externalId,
            'status' => 'pending',
            'amount' => $product->price
        ]);

        $params = [
            'external_id' => $externalId,
            'amount' => $product->price,
            'invoice_duration' => 3600,
        ];

        $invoice = $this->createInvoice($params);

        $payment->update([
            'payment_url' => $invoice['invoice_url'],
        ]);

        $product->update([
            'status' => 'pending'
        ]);

        return response()->json([
            'status' => 'success',
        ]);
    }

    public function callback(Request $request)
    {
        try {

            $payment = Bills::where('external_id', $request->external_id)->first();
            $callback_token = env('XENDIT_CALLBACK_TOKEN');

            if ($request->header('x-callback-token') !== $callback_token) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid callback token'
                ], 400);
            }

            // if ($payment) {
            //     $payment->update([
            //         'status' => $request->status,
            //     ]);

            //     $product = Product::find($payment->product_id);

            //     if ($request->status === 'PAID') {
            //         $product->update([
            //             'status' => 'paid'
            //         ]);
            //     } else {
            //         $product->update([
            //             'status' => 'expired'
            //         ]);
            //     }
            // }

            return response()->json([
                'status' => 'success',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}