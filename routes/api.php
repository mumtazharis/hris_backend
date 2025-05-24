<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CheckClockController;
use App\Http\Controllers\CheckClockSettingController;
use App\Http\Controllers\CheckClockSettingTimesController;
use App\Http\Controllers\PaymentController;

Route::withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('/completeRegister', [AuthController::class, 'completeRegister'])->middleware('auth:sanctum');
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('signupWithGoogle', [AuthController::class, 'signupWithGoogle'])->name('login_google');
    Route::post('loginWithGoogle', [AuthController::class, 'loginWithGoogle'])->name('login_google');
    // Route::post('payment', [PaymentController::class, 'createXenditInvoice'])->name('payment');
    Route::post('payment', [PaymentController::class, 'createInvoice'])->name('payment');
    Route::post('order_summary', [PaymentController::class, 'getOrderSummary'])->name('order_summary');
});

Route::middleware('auth:sanctum','role:employee,admin')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    // Resource routes
    Route::resource('check-clocks', CheckClockController::class);
    Route::resource('check-clock-settings', CheckClockSettingController::class);
    Route::resource('check-clock-setting-times', CheckClockSettingTimesController::class);
});