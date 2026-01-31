<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $admin = Auth::user();
        $departmentId = $admin->department_id;

        return view('admin.admindashboard', [
            // Filter reports by department
            'totalReports' => Report::whereHas('user', function($query) use ($departmentId) {
                $query->where('department_id', $departmentId);
            })->count(),

            'pendingReports' => Report::where('status', 'Pending')
                ->whereHas('user', function($query) use ($departmentId) {
                    $query->where('department_id', $departmentId);
                })->count(),

            'underReview' => Report::where('status', 'Under Review')
                ->whereHas('user', function($query) use ($departmentId) {
                    $query->where('department_id', $departmentId);
                })->count(),

            'resolvedReports' => Report::where('status', 'Resolved')
                ->whereHas('user', function($query) use ($departmentId) {
                    $query->where('department_id', $departmentId);
                })->count(),

            // Only recent reports from same department
            'recentReports' => Report::whereHas('user', function($query) use ($departmentId) {
                    $query->where('department_id', $departmentId);
                })
                ->with(['user', 'category']) // Load relationships
                ->latest()
                ->take(10)
                ->get(),

            // Only users from same department (exclude admins)
            'totalUsers' => User::where('department_id', $departmentId)
                ->where('is_admin', 0)
                ->count(),
        ]);
    }
}