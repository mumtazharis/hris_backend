<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CheckClockController;
use App\Http\Controllers\CheckClockControllerEmp;
use App\Http\Controllers\CheckClockSettingController;
use App\Http\Controllers\CheckClockSettingTimesController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\employee\CheckClockControllerEmp as EmployeeCheckClockControllerEmp;
use App\Http\Controllers\employee\DashboardController as EmployeeDashboardController;
use App\Http\Controllers\employee\OvertimeController as EmployeeOvertimeController;
use App\Http\Controllers\employee\NotificationController as EmployeeNotificationController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\FormDataController;
use App\Http\Controllers\MailTest;
use App\Http\Controllers\NotificationController;
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

    Route::get('server-time', [EmployeeCheckClockControllerEmp::class, 'getServerTime']);
});


// ROLE ADMIN
Route::middleware('auth:sanctum','role:admin')->group(function () {
    Route::get('check-token-admin', [AuthController::class, 'checkToken']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/getUser', [UserController::class, 'getUser']);

    Route::patch('/user/change-password', [UserController::class, 'changePassword']);

    Route::get("/profile", [ProfileController::class, 'show']);
    Route::patch("/profile", [ProfileController::class, 'update']);
    
    // CC api
    // Route::resource('check-clocks', CheckClockController::class);
    Route::get('check-clocks', [CheckClockController::class, 'index']);
    Route::post('check-clocks',[ CheckClockController::class, 'store']);
    Route::get('cc-employee-data', [CheckClockController::class, 'getEmployeeData']);
    Route::post('reject-check-clock', [CheckClockController::class, 'reject']);
    Route::put('check-clock-approval/{id}', [CheckClockController::class, 'approval']);

    // cc times
    Route::resource('check-clock-setting-times', CheckClockSettingTimesController::class);
    
    // cc setting location
    Route::post('check-clock-rule', [CheckClockSettingController::class, 'update']);
    
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
    Route::get('employee-overtime-form-data', [FormDataController::class, 'getEmployee']);
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


    Route::get("/overtime-settings", [OvertimeSettingController::class, 'index']);
    Route::post("/overtime-settings", [OvertimeSettingController::class, 'store']);
    Route::put("/overtime-settings/{id}", [OvertimeSettingController::class, 'update']);
    Route::delete("/overtime-settings/{id}", [OvertimeSettingController::class, 'delete']);
    Route::patch('/overtime-settings/status', [OvertimeSettingController::class, 'changeStatus']);

    Route::get("/overtime", [OvertimeController::class, 'index']);
    Route::post("/overtime", [OvertimeController::class, 'create']);
    Route::patch("/overtime/approval", [OvertimeController::class, 'approval']);
    Route::delete("/overtime/{id}", [OvertimeController::class, 'delete']);

    Route::get("/company", [CompanyController::class, 'show']);
   
    Route::get("/getCompanyDepPos", [CompanyController::class, 'getCompanyDepPos']);



    Route::patch("/company", [CompanyController::class, 'editCompany']);
    Route::post("/department", [CompanyController::class, 'addDepartment']);
    Route::patch("/department", [CompanyController::class, 'editDepartment']);
    Route::delete("/department", [CompanyController::class, 'deleteDepartment']);
    Route::post("/position", [CompanyController::class, 'addPosition']);
    Route::patch("/position", [CompanyController::class, 'editPosition']);
    Route::delete("/position", [CompanyController::class, 'deletePosition']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread', [NotificationController::class, 'unread']);
    Route::post('/notifications/read/{id}', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
});

// ROLE EMPLOYEE
Route::middleware('auth:sanctum','role:employee')->group(function () {
    Route::get('/get-user-employee', [UserController::class, 'getUserEmployee']);

    Route::post('/logout-employee', [AuthController::class, 'logout']);
    Route::get('check-clock', [EmployeeCheckClockControllerEmp::class, 'index']);
    Route::post('check-clock', [EmployeeCheckClockControllerEmp::class, 'store']);
    Route::get('check-clockin', [EmployeeCheckClockControllerEmp::class, 'checkClockIn']);
    Route::get("/employee/dashboard", [EmployeeDashboardController::class, 'dashboard']);
    Route::get("/employee/overtime", [EmployeeOvertimeController::class, 'index']);
    Route::post("/employee/overtime", [EmployeeOvertimeController::class, 'create']);

    Route::get('/employee/notifications', [EmployeeNotificationController::class, 'index']);
    Route::get('/employee/notifications/unread', [EmployeeNotificationController::class, 'unread']);
    Route::post('/employee/notifications/read/{id}', [EmployeeNotificationController::class, 'markAsRead']);
    Route::post('/employee/notifications/read-all', [EmployeeNotificationController::class, 'markAllAsRead']);
});