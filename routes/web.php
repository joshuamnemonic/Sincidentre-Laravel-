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

    // Admin Users
    Route::get('/admin/users', [UserManagementController::class, 'index'])
        ->name('admin.users');
    Route::get('/admin/users/{id}', [UserManagementController::class, 'show'])
        ->name('admin.users.show');
    Route::post('/admin/users/{id}/suspend', [UserManagementController::class, 'suspend'])
        ->name('admin.users.suspend');
    Route::delete('/admin/users/{id}', [UserManagementController::class, 'destroy'])
        ->name('admin.users.destroy');
        

    // Admin Handle Reports
    // Handle Reports routes
   Route::prefix('admin')->group(function () {
    Route::get('/handle-reports', [HandleReportsController::class, 'index'])->name('admin.handlereports');
    Route::get('/handle-reports/{id}', [HandleReportsController::class, 'show'])->name('admin.handlereports.show');
    Route::post('/admin/handle-reports/{id}/update', [HandleReportsController::class, 'update'])
    ->name('admin.handlereports.update');

});
    //CATEGORY MANAGEMENT
   Route::prefix('admin')->name('admin.')->middleware(['auth', 'is_admin'])->group(function () {
    Route::resource('categories', CategoryController::class);
});




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
Route::get('/sinclogin', [AuthController::class, 'showLogin'])->name('sinclogin');
Route::post('/sinclogin', [AuthController::class, 'login'])->name('sinclogin.post');

Route::get('/sincregister', [AuthController::class, 'showRegister'])->name('sincregister');
Route::post('/sincregister', [AuthController::class, 'register'])->name('sincregister.post');

Route::get('/register', fn () => redirect()->route('sincregister')); // redirect default register
Route::get('/login', fn () => redirect()->route('sinclogin')); // redirect default login

// Logout
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// --------------------
// INCIDENT SYSTEM
// --------------------
Route::get('/newreport', [NewReportController::class, 'create'])->name('newreport');
Route::post('/newreport', [NewReportController::class, 'store'])->name('reports.store'); // ✅ POST goes to /reports
Route::get('/myreports', [MyReportsController::class, 'index'])->name('myreports');
Route::get('/reports/{id}', [ReportController::class, 'show'])->name('report.show');
// ❌ No Jetstream/Breeze default auth routes
// require __DIR__.'/auth.php';