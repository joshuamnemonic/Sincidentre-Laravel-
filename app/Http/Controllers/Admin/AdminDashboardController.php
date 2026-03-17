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
            $positionCode = trim((string) ($admin->routing_position_code ?? ''));
            $fullName = strtolower(trim((string) (($admin->first_name ?? '') . ' ' . ($admin->last_name ?? ''))));

            $baseQuery->where(function ($query) use ($positionCode, $fullName) {
                if ($positionCode !== '') {
                    $query->where('assigned_position_code', $positionCode);
                }

                if ($fullName !== '') {
                    if ($positionCode !== '') {
                        $query->orWhereRaw('LOWER(assigned_to) = ?', [$fullName]);
                    } else {
                        $query->whereRaw('LOWER(assigned_to) = ?', [$fullName]);
                    }
                }

                if ($positionCode === '' && $fullName === '') {
                    $query->whereRaw('1 = 0');
                }
            });
        } else {
            $baseQuery->whereHas('user', function($query) use ($departmentId) {
                $query->where('department_id', $departmentId);
            })->whereHas('category', function ($query) {
                $query->whereNotIn('classification', ['Major', 'Grave']);
            });
        }

        return view('admin.admindashboard', [
            'totalReports' => (clone $baseQuery)->count(),

            'pendingReports' => (clone $baseQuery)->where('status', Report::STATUS_PENDING)->count(),

            'underReview' => (clone $baseQuery)->where('status', Report::STATUS_UNDER_REVIEW)->count(),

            'resolvedReports' => (clone $baseQuery)->where('status', Report::STATUS_RESOLVED)->count(),

            'approvedReports' => (clone $baseQuery)->where('status', Report::STATUS_APPROVED)->count(),

            'rejectedReports' => (clone $baseQuery)->where('status', Report::STATUS_REJECTED)->count(),

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

