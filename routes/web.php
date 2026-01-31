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
Route::middleware(['auth', 'is_admin'])->group(function () {
    
    // Admin Dashboard
    Route::get('/admin/admindashboard', [AdminDashboardController::class, 'index'])
        ->name('admin.admindashboard');

    // Admin Reports (Review Queue)
    Route::get('/admin/reports', [ReportManagementController::class, 'index'])
        ->name('admin.reports');
    Route::get('/admin/reports/{id}', [ReportManagementController::class, 'show'])
        ->name('admin.reports.show');
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

        Route::put('/handle-reports/{id}/update', 
            [HandleReportsController::class, 'update']
        )->name('admin.handlereports.update');
    });
});

// CATEGORY MANAGEMENT
Route::prefix('admin')->name('admin.')->middleware(['auth', 'is_admin'])->group(function () {
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

// ADD THIS: Give your login route the 'login' name that Laravel expects
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');

// OR if you want to keep the redirect:
// Route::get('/login', fn () => redirect()->route('sinclogin'))->name('login');

// Redirect register route
Route::get('/register', fn () => redirect()->route('sincregister'));

// Logout
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Email Verification Routes
Route::get('/email/verify', fn() => view('auth.verify-email'))->middleware('auth')->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware(['auth', 'signed'])
    ->name('verification.verify');
Route::post('/email/resend', [AuthController::class, 'resendVerification'])
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.resend');

// --------------------
// INCIDENT SYSTEM
// --------------------
Route::get('/newreport', [ReportController::class, 'create'])->name('newreport');
Route::post('/newreport', [ReportController::class, 'store'])->name('reports.store');
Route::get('/myreports', [MyReportsController::class, 'index'])->name('myreports');
Route::get('/reports/{id}', [ReportController::class, 'show'])->name('report.show');

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

Route::get('/email/verify/{id}/{hash}', function (Request $request) {
    $user = User::findOrFail($request->route('id'));
    
    if (!hash_equals(sha1($user->getEmailForVerification()), (string) $request->route('hash'))) {
        return redirect()->route('sinclogin')->withErrors(['email' => 'Invalid verification link.']);
    }
    
    if ($user->hasVerifiedEmail()) {
        return redirect()->route('sinclogin')->with('info', 'Email already verified. Please login.');
    }
    
    $user->markEmailAsVerified();
    
    // AUTO-LOGIN the user and redirect to dashboard
    Auth::login($user);
    
    // Use the named route instead of hardcoded path
    return redirect()->route('dashboard')->with('success', 'Email verified successfully! Welcome!');
    
})->middleware(['signed'])->name('verification.verify');

// DEPARTMENT MANAGEMENT (All admins can manage departments)
Route::prefix('admin')->middleware(['auth', 'is_admin'])->name('admin.')->group(function () {
    Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
    Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
    Route::put('/departments/{id}', [DepartmentController::class, 'update'])->name('departments.update');
    Route::delete('/departments/{id}', [DepartmentController::class, 'destroy'])->name('departments.destroy');
});

// Activity Logs
Route::get('/admin/activity-logs', [ActivityLogController::class, 'index'])
    ->middleware(['auth', 'is_admin'])
    ->name('admin.activitylogs');