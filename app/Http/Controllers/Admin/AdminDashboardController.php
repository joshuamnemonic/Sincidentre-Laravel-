<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\ReportResponse;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AdminDashboardController extends Controller
{
    private function buildNextActionLabel(Report $report): string
    {
        $status = Report::normalizeStatus($report->status);

        if ($status === Report::STATUS_APPROVED && !$report->hearing_date) {
            return 'Schedule hearing (Form 2303).';
        }

        if ($report->hearing_date && !$report->reprimand_issued_at) {
            return 'Continue post-hearing handling or issue Form 2304.';
        }

        if ($report->reprimand_issued_at && !$report->suspension_issued_at) {
            return 'Monitor acknowledgment or proceed with Form 2305 if needed.';
        }

        return 'Open report to continue handling updates.';
    }

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

        $visibleReportIds = (clone $baseQuery)->pluck('id');

        $lastManagedActiveResponse = ReportResponse::query()
            ->where('dsdo_id', $admin->id)
            ->whereIn('report_id', $visibleReportIds)
            ->whereHas('report', function ($query) {
                $query->whereIn('status', [Report::STATUS_APPROVED, Report::STATUS_UNDER_REVIEW]);
            })
            ->with(['report.user', 'report.category'])
            ->orderByDesc('created_at')
            ->first();

        $lastManagedResponse = $lastManagedActiveResponse ?: ReportResponse::query()
            ->where('dsdo_id', $admin->id)
            ->whereIn('report_id', $visibleReportIds)
            ->with(['report.user', 'report.category'])
            ->orderByDesc('created_at')
            ->first();

        $lastManagedReport = $lastManagedResponse?->report;
        $lastManagedNextAction = $lastManagedReport ? $this->buildNextActionLabel($lastManagedReport) : null;

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

            'lastManagedReport' => $lastManagedReport,
            'lastManagedResponse' => $lastManagedResponse,
            'lastManagedNextAction' => $lastManagedNextAction,
        ]);
    }
}

