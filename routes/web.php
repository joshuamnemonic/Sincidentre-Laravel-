<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NewReportController;
use App\Http\Controllers\MyReportsController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\ReportManagementController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Admin\HandleReportsController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AdminProfileController;


Route::get('/', function () {
    return view('welcome');
});

// --------------------
// USER DASHBOARD
// --------------------
Route::get('/user/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// --------------------
// ADMIN DASHBOARD & MANAGEMENT
// --------------------
Route::middleware(['auth', 'is_department_student_discipline_officer', 'pending_handling_response'])->group(function () {
    
    // Department Student Discipline Officer Dashboard
    Route::get('/admin/admindashboard', [AdminDashboardController::class, 'index'])
        ->name('admin.admindashboard');

    // Admin Reports (Review Queue)
    Route::get('/admin/reports', [ReportManagementController::class, 'index'])
        ->name('admin.reports');
    Route::get('/admin/reports/{id}', [ReportManagementController::class, 'show'])
        ->name('admin.reports.show');
    Route::patch('/admin/reports/{id}/escalate', [ReportManagementController::class, 'escalateToTopManagement'])
        ->name('admin.reports.escalate');
    Route::patch('/admin/reports/{id}/approve', [ReportController::class, 'approve'])
        ->name('admin.reports.approve');
    Route::patch('/admin/reports/{id}/reject', [ReportController::class, 'reject'])
        ->name('admin.reports.reject');

    // Admin Users Management
    Route::get('/admin/users', [UserManagementController::class, 'index'])
        ->name('admin.users');
    Route::get('/admin/users/{id}', [UserManagementController::class, 'show'])
        ->name('admin.users.show');
    Route::get('/admin/users/{id}/edit', [UserManagementController::class, 'edit'])
        ->name('admin.users.edit');
    Route::put('/admin/users/{id}', [UserManagementController::class, 'update'])
        ->name('admin.users.update');
    Route::post('/admin/users/{id}/suspend', [UserManagementController::class, 'suspend'])
        ->name('admin.users.suspend');
    Route::post('/admin/users/{id}/activate', [UserManagementController::class, 'activate'])
        ->name('admin.users.activate');
    Route::post('/admin/users/{id}/deactivate', [UserManagementController::class, 'deactivate'])
        ->name('admin.users.deactivate');
    Route::delete('/admin/users/{id}', [UserManagementController::class, 'destroy'])
        ->name('admin.users.destroy');

    // Admin Handle Reports
Route::prefix('admin')->group(function () {
    Route::get('/handle-reports', 
        [HandleReportsController::class, 'index']
    )->name('admin.handlereports');

    Route::get('/handle-reports/{id}', 
        [HandleReportsController::class, 'show']
    )->name('admin.handlereports.show');

    Route::post('/handle-reports/{id}/schedule-hearing',
        [HandleReportsController::class, 'scheduleHearing']
    )->name('admin.handlereports.schedule-hearing');

    Route::post('/handle-reports/{id}/notify-respondent',
        [HandleReportsController::class, 'notifyRespondent']
    )->name('admin.handlereports.notify-respondent');

    Route::get('/handle-reports/{id}/print-call-slip',
        [HandleReportsController::class, 'printCallSlip']
    )->name('admin.handlereports.print-call-slip');

    Route::post('/handle-reports/{id}/issue-reprimand',
        [HandleReportsController::class, 'issueReprimand']
    )->name('admin.handlereports.issue-reprimand');

    Route::get('/handle-reports/{id}/print-reprimand',
        [HandleReportsController::class, 'printReprimand']
    )->name('admin.handlereports.print-reprimand');

    Route::post('/handle-reports/{id}/issue-suspension',
        [HandleReportsController::class, 'issueSuspension']
    )->name('admin.handlereports.issue-suspension');

    Route::post('/handle-reports/{id}/escalate-top-management',
        [HandleReportsController::class, 'escalateToTopManagement']
    )->name('admin.handlereports.escalate-top-management');

    Route::get('/handle-reports/{id}/print-suspension',
        [HandleReportsController::class, 'printSuspension']
    )->name('admin.handlereports.print-suspension');

    Route::put('/handle-reports/{id}/update', 
        [HandleReportsController::class, 'update']
    )->name('admin.handlereports.update');
    
    // Optional: Additional routes
    Route::post('/handle-reports/bulk-update', 
        [HandleReportsController::class, 'bulkUpdate']
    )->name('admin.handlereports.bulk-update');
    
    Route::get('/handle-reports/export', 
        [HandleReportsController::class, 'export']
    )->name('admin.handlereports.export');
});

});

Route::middleware(['auth'])->group(function () {
    Route::post('/reports/{id}/acknowledge-reprimand', [HandleReportsController::class, 'acknowledgeReprimand'])
        ->name('reports.acknowledge-reprimand');
    Route::post('/reports/{report}/confirm-hearing-notice', [HandleReportsController::class, 'confirmHearingNotice'])->name('reports.confirmHearingNotice');
});

// CATEGORY MANAGEMENT
Route::prefix('admin')->name('admin.')->middleware(['auth', 'is_top_management', 'pending_handling_response'])->group(function () {
    Route::resource('categories', CategoryController::class);
});

// --------------------
// PROFILE (protected)
// --------------------
Route::middleware('auth')->group(function () {
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
});

// --------------------
// CUSTOM AUTH ROUTES
// --------------------

// Login
Route::get('/sinclogin', [AuthController::class, 'showLogin'])->name('sinclogin');
Route::post('/sinclogin', [AuthController::class, 'login'])->name('sinclogin.post');

// Register
Route::get('/sincregister', [AuthController::class, 'showRegister'])->name('sincregister');
Route::post('/sincregister', [AuthController::class, 'register'])->name('sincregister.post');
Route::get('/sincregister/verify-otp', [AuthController::class, 'showOtpForm'])->name('sincregister.otp.form');
Route::post('/sincregister/verify-otp', [AuthController::class, 'verifyOtp'])->name('sincregister.otp.verify');
Route::post('/sincregister/resend-otp', [AuthController::class, 'resendOtp'])->name('sincregister.otp.resend');

// ADD THIS: Give your login route the 'login' name that Laravel expects
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');

// OR if you want to keep the redirect:
// Route::get('/login', fn () => redirect()->route('sinclogin'))->name('login');

// Redirect register route
Route::get('/register', fn () => redirect()->route('sincregister'));

// Logout
Route::post('/logout', [AuthController::class, 'logout'])->middleware(['auth', 'pending_handling_response'])->name('logout');

// Email verification prompt (users are verified via OTP during registration)
Route::get('/email/verify', fn() => view('auth.verify-email'))->middleware('auth')->name('verification.notice');

// --------------------
// INCIDENT SYSTEM
// --------------------
Route::get('/newreport', [ReportController::class, 'create'])->name('newreport');
Route::post('/newreport', [ReportController::class, 'store'])->name('reports.store');
Route::get('/myreports', [MyReportsController::class, 'index'])->name('myreports');
Route::get('/reports/{id}', [ReportController::class, 'show'])->name('report.show');

// DEPARTMENT MANAGEMENT (Top Management only)
Route::prefix('admin')->middleware(['auth', 'is_top_management', 'pending_handling_response'])->name('admin.')->group(function () {
    Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
    Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
    Route::put('/departments/{id}', [DepartmentController::class, 'update'])->name('departments.update');
    Route::delete('/departments/{id}', [DepartmentController::class, 'destroy'])->name('departments.destroy');
});

// Activity Logs
Route::get('/admin/activity-logs', [ActivityLogController::class, 'index'])
    ->middleware(['auth', 'is_department_student_discipline_officer', 'pending_handling_response'])
    ->name('admin.activitylogs');

// Analytics Routes
Route::middleware(['auth', 'is_department_student_discipline_officer', 'pending_handling_response'])->prefix('admin')->name('admin.')->group(function () {
    
    // Analytics Dashboard
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
    
    // Export Analytics
    Route::get('/analytics/export', [AnalyticsController::class, 'export'])->name('analytics.export');
    
});

// Department Student Discipline Officer Profile Routes
Route::middleware(['auth', 'is_department_student_discipline_officer', 'pending_handling_response'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/profile', [AdminProfileController::class, 'show'])->name('profile');
    Route::patch('/profile', [AdminProfileController::class, 'update'])->name('profile.update');
});


