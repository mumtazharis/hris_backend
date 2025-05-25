<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CheckClockController;
use App\Http\Controllers\CheckClockSettingController;
use App\Http\Controllers\CheckClockSettingTimesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\MailTest;
use App\Http\Controllers\ResetPasswordController;

Route::withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('/completeRegister', [AuthController::class, 'completeRegister'])->middleware('auth:sanctum');
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('signup-with-google', [AuthController::class, 'signupWithGoogle'])->name('login_google');
    Route::post('signin-with-google', [AuthController::class, 'loginWithGoogle'])->name('login_google');
    Route::post('mail-test', [MailTest::class, 'store'])->name('mail_test');
    Route::post('reset-password', [ResetPasswordController::class, 'reqResetPassword'])->name('reset_password');
    Route::post('reset-process', [ResetPasswordController::class, 'resetPassword'])->name('reset_password_process');
    Route::post('token-checker', [ResetPasswordController::class, 'checkToken'])->name('check_token');

    Route::get('dashboardnologin', [DashboardController::class, 'dashboard']);
    Route::get('employee', [EmployeeController::class, 'index']);
    Route::get('employee/{employee_id}', [EmployeeController::class, 'show']);
    Route::post('employee', [EmployeeController::class, 'store']);
    Route::patch('employee/{employee_id}', [EmployeeController::class, 'update']);
    Route::delete('employee/{employee_id}', [EmployeeController::class, 'destroy']);
    // Route::resource('employee', EmployeeController::class);
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

    // Route::post('/verify-token', function () {
    // return response()->json([
    //     // 'user' => $request->user(),
    //     'status' => 'Token is valid',
    // ], 200);
    
    Route::get('dashboard', [DashboardController::class, 'approvalStatus']);
});