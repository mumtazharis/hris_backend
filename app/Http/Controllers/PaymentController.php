<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Product;
use App\Models\Bills;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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

    public function getOrderSummary(Request $request): JsonResponse
    {
        // if (!Auth::check()) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'User not authenticated.'
        //     ], 401);
        // }

        // $loggedInUserId = 1;

        $userId = $request->input('user_id');

        if (!$userId) {
            return response()->json([
                'status' => 'error',
                'message' => 'User ID is required.'
            ], 400);
        }

        $pendingBills = Bills::where('user_id', $userId)
            ->where('status', 'pending')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $pendingBills
        ]);
    }

    public function createInvoice(Request $request)
    {
        $api_key = base64_encode(env('XENDIT_SECRET_KEY'));
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . $api_key,
        ];


        //  if (!Auth::check()) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'User not authenticated.'
        //     ], 401);
        // }

        // $loggedInUserId = Auth::id(); // Mendapatkan ID user yang sedang login

        // $loggedInUserId = 7;
        $userId = $request->input('user_id');

        if (!$userId) {
            return response()->json([
                'status' => 'error',
                'message' => 'User ID is required.'
            ], 400);
        }
        $currentPeriod = now()->format('m-Y');

        $bill = DB::table('bills')
            ->where('user_id', $userId)
            ->where('period', $currentPeriod)
            ->where('status', 'pending')
            ->latest('created_at')
            ->first();

        if (!$bill) {
            return response()->json([
                'status' => 'error',
                'message' => 'No pending bill found for the current period.',
            ], 404);
        }

        $invoiceDuration = 1 * 24 * 60 * 60; // 1 hari

        $res = Http::withHeaders($headers)->post('https://api.xendit.co/v2/invoices', [
            'external_id' => $bill->payment_id,
            'total_employee' => $bill->total_employee,
            'amount' => $bill->amount,
            'invoice_duration' => $invoiceDuration,
        ]);

        $response = json_decode($res->body(), true);

        return response()->json([
            'status' => 'success',
            'message' => 'Invoice created successfully.',
            'invoice_url' => $response['invoice_url'] ?? null,
            'external_id' => $response['external_id'] ?? null,
            'amount' => $response['amount'] ?? null,
            'xendit_id' => $response['id'] ?? null,
        ], 200);
    }

    public function handle(Request $request)
    {
        $payment_id = $request->input('external_id');
        $status = strtoupper($request->input('status')); // Ubah ke huruf besar

        if ($status === 'PAID') {
            $updated = DB::table('bills')
                ->where('payment_id', $payment_id)
                ->update(['status' => 'paid']);

            if ($updated === 0) {
                // Tidak ada record yang terupdate
                return response()->json([
                    'message' => 'No matching bill found or already updated.',
                    'payment_id' => $payment_id,
                    'status' => $status
                ], 404);
            }
        } else {
            // Status tidak dikenali
            return response()->json([
                'message' => 'Unrecognized status from Xendit',
                'status' => $status
            ], 400);
        }

        // Sukses
        return response()->json(['message' => 'Webhook received'], 200);
    }
}
