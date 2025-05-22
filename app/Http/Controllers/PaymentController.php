<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Otnansirk\Xendit\Facades\Xendit;
use Xendit\Invoice\CreateInvoiceRequest;
use Xendit\XenditSdkException;

class PaymentController extends Controller
{
    public function createXenditInvoice(Request $request): JsonResponse
    {
        $xenditService = Xendit::invoice();

        // Deklarasikan semua nilai sebagai variabel terlebih dahulu
        $externalId = 'test1234-';
        $description = 'Test Invoice from Laravel Controller';
        $amount = 15000;
        $invoiceDuration = 172800; // 2 hari dalam detik
        $currency = 'IDR';
        $reminderTime = 1;

        $createInvoiceRequest = new CreateInvoiceRequest([
            'external_id'    => $externalId,
            'description'    => $description,
            'amount'         => $amount,
            'invoice_duration' => $invoiceDuration,
            'currency'       => $currency,
            'reminder_time'  => $reminderTime
        ]);

        try {
            $result = $xenditService->createInvoice($createInvoiceRequest);

            return response()->json([
                'status'  => 'success',
                'message' => 'Invoice created successfully',
                'data'    => $result
            ]);
        } catch (XenditSdkException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to create invoice',
                'error'   => $e->getMessage(),
                'full_error' => $e->getFullError()
            ], 500);
        }
    }
}
