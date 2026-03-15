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

        $baseQuery = Report::query();
        if ($admin->is_top_management) {
            $baseQuery->whereHas('category', function ($query) {
                $query->whereIn('classification', ['Major', 'Grave']);
            });
        } else {
            $baseQuery->whereHas('user', function($query) use ($departmentId) {
                $query->where('department_id', $departmentId);
            });
        }

        return view('admin.admindashboard', [
            'totalReports' => (clone $baseQuery)->count(),

            'pendingReports' => (clone $baseQuery)->whereRaw('LOWER(status) = ?', ['pending'])->count(),

            'underReview' => (clone $baseQuery)->whereRaw('LOWER(status) = ?', ['under review'])->count(),

            'resolvedReports' => (clone $baseQuery)->whereRaw('LOWER(status) = ?', ['resolved'])->count(),

            'approvedReports' => (clone $baseQuery)->whereRaw('LOWER(status) = ?', ['approved'])->count(),

            'rejectedReports' => (clone $baseQuery)->whereRaw('LOWER(status) = ?', ['rejected'])->count(),

            'recentReports' => (clone $baseQuery)
                ->with(['user', 'category'])
                ->latest()
                ->take(10)
                ->get(),

            // Only regular users in same department (exclude privileged accounts)
            'totalUsers' => User::where('department_id', $departmentId)
                ->where('is_department_student_discipline_officer', 0)
                ->where('is_top_management', 0)
                ->count(),
        ]);
    }
}

