<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Contracts\View\View;
use Otnansirk\Xendit\Facades\Xendit;
use Xendit\Invoice\CreateInvoiceRequest;
use Xendit\XenditSdkException;

// Route::get(uri: '/', action: function (): View {
//     return view(view: 'vendor.xendivel.checkout');
// });
// Route::get(uri: '/invoice', action: function (): View {
//     return view(view: 'vendor.xendivel.invoice');
// });
Route::get('/test-xendit', function () {
    $xendit = Xendit::invoice();

    $create_invoice_request = new CreateInvoiceRequest([
        'external_id' => 'test1234',
        'description' => 'Test Invoice',
        'amount' => 10000,
        'invoice_duration' => 172800,
        'currency' => 'IDR',
        'reminder_time' => 1
    ]);

    $for_user_id = "62efe4c33e45694d63f585f0"; // optional for sub-accounts

    try {
        $result = $xendit->createInvoice($create_invoice_request, $for_user_id);
        dd($result);
    } catch (XenditSdkException $e) {
        echo 'Exception when calling createInvoice: ', $e->getMessage(), PHP_EOL;
        echo 'Full Error: ', json_encode($e->getFullError()), PHP_EOL;
    }
});
