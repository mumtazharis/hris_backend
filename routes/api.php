<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CheckClockController;
use App\Http\Controllers\CheckClockSettingController;
use App\Http\Controllers\CheckClockSettingTimesController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\FormDataController;
use App\Http\Controllers\MailTest;
use App\Http\Controllers\OvertimeController;
use App\Http\Controllers\OvertimeSettingController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Models\Document;
use App\Models\OvertimeSetting;

Route::withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('/completeRegister', [AuthController::class, 'completeRegister'])->middleware('auth:sanctum');
    Route::post('login', [AuthController::class, 'login'])->name('loginEmployee');
    Route::post('login-employee', [AuthController::class, 'loginEmployee'])->name('login');
    Route::post('signup-with-google', [AuthController::class, 'signupWithGoogle'])->name('login_google');
    Route::post('signin-with-google', [AuthController::class, 'loginWithGoogle'])->name('login_google');
    Route::post('mail-test', [MailTest::class, 'store'])->name('mail_test');
    Route::post('reset-password', [ResetPasswordController::class, 'reqResetPassword'])->name('reset_password');
    Route::post('reset-process', [ResetPasswordController::class, 'resetPassword'])->name('reset_password_process');
    Route::post('token-checker', [ResetPasswordController::class, 'checkToken'])->name('check_token');

    // Route::post('payment', [PaymentController::class, 'createXenditInvoice'])->name('payment');
    Route::post('order_summary', [PaymentController::class, 'getOrderSummary'])->name('order_summary');
    Route::post('/xendit/webhook', [PaymentController::class, 'handle']);
    // Route::get('/payment', [DocumentController::class, 'payment']);
    // Route::get('/employees/export-csv', [EmployeeController::class, 'exportCsv'])->name('employees.exportCsv');
});


// ROLE ADMIN
Route::middleware('auth:sanctum','role:admin')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/getUser', [UserController::class, 'getUser']);

    Route::get("/profile", [ProfileController::class, 'show']);
    Route::patch("/profile", [ProfileController::class, 'update']);
    // Resource routes
    Route::resource('check-clocks', CheckClockController::class);
    // Route::resource('check-clock-settings', CheckClockSettingController::class);
    Route::resource('check-clock-setting-times', CheckClockSettingTimesController::class);
    
    Route::post('check-clock-rule', [CheckClockSettingController::class, 'update']);
    Route::put('check-clock-approval/{id}', [CheckClockController::class, 'approval']);
    //payment
    Route::get('/payment-history', [PaymentController::class, 'index']);
    Route::get('payment', [PaymentController::class, 'createInvoice'])->name('payment');
    // Route::post('/verify-token', function () {
    // return response()->json([
    //     // 'user' => $request->user(),
    //     'status' => 'Token is valid',
    // ], 200);
    Route::get('bank', [FormDataController::class, 'getBank']);
    Route::get('department-position', [FormDataController::class, 'getDepartmentPosition']);
    Route::get('dashboard', [DashboardController::class, 'dashboard']);

    Route::get('/employees', [EmployeeController::class, 'index']);
    Route::get('/employees/{id}', [EmployeeController::class, 'show']);
    Route::post('/employees', [EmployeeController::class, 'store']);
    Route::patch('/employees/{id}', [EmployeeController::class, 'update']);
    Route::delete('/employees/{id}', [EmployeeController::class, 'permanentDelete']);
    Route::get('/employee/export-csv', [EmployeeController::class, 'exportCsv']);
    Route::post('/employees/preview-csv', [EmployeeController::class, 'previewCsv']);
    Route::post('/employees/confirm-import', [EmployeeController::class, 'confirmImport']);
    Route::post('/employees/import-csv', [EmployeeController::class, 'importCsv']);
    Route::post('/employees/{employee_id}/reset-password', [EmployeeController::class, 'resetPassword']);
    Route::post('/documents' ,[DocumentController::class, 'store']);
    Route::get('/documents/{employee_id}' ,[DocumentController::class, 'getEmployeeDocument']);
    Route::get('/documents/download/{id}' ,[DocumentController::class, 'download']);


    Route::get("/overtime_settings", [OvertimeSettingController::class, 'index']);
    Route::post("/overtime_settings", [OvertimeSettingController::class, 'store']);
    Route::put("/overtime_settings/{id}", [OvertimeSettingController::class, 'update']);
    Route::delete("/overtime_settings/{id}", [OvertimeSettingController::class, 'delete']);

    Route::get("/overtime", [OvertimeController::class, 'index']);
    Route::post("/overtime", [OvertimeController::class, 'create']);
    Route::patch("/overtime/{id}", [OvertimeController::class, 'approval']);
    Route::delete("/overtime/{id}", [OvertimeController::class, 'delete']);

    Route::get("/company", [CompanyController::class, 'show']);
   
    Route::get("/getCompanyDepPos", [CompanyController::class, 'getCompanyDepPos']);



    Route::post("/department", [CompanyController::class, 'addDepartment']);
    Route::patch("/department", [CompanyController::class, 'editDepartment']);
    Route::delete("/department", [CompanyController::class, 'deleteDepartment']);
    Route::post("/position", [CompanyController::class, 'addPosition']);
    Route::patch("/position", [CompanyController::class, 'editPosition']);
    Route::delete("/position", [CompanyController::class, 'deletePosition']);
});

// ROLE EMPLOYEE
Route::middleware('auth:sanctum','role:employee')->group(function () {


});