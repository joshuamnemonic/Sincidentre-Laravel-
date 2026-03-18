<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Report;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        $counts = Cache::remember("dashboard:counts:{$userId}", now()->addMinutes(2), function () use ($userId) {
            return Report::query()
                ->where('user_id', $userId)
                ->selectRaw('COUNT(*) as total_reports')
                ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending_reports', [Report::STATUS_PENDING])
                ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as approved_reports', [Report::STATUS_APPROVED])
                ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as rejected_reports', [Report::STATUS_REJECTED])
                ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as under_review_reports', [Report::STATUS_UNDER_REVIEW])
                ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as resolved_reports', [Report::STATUS_RESOLVED])
                ->first();
        });

        $totalReports = (int) ($counts->total_reports ?? 0);
        $pendingReports = (int) ($counts->pending_reports ?? 0);
        $approvedReports = (int) ($counts->approved_reports ?? 0);
        $rejectedReports = (int) ($counts->rejected_reports ?? 0);
        $underReviewReports = (int) ($counts->under_review_reports ?? 0);
        $resolvedReports = (int) ($counts->resolved_reports ?? 0);

        $attentionReports = $pendingReports + $underReviewReports + $rejectedReports;

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $monthlyCounts = Report::query()
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->selectRaw('COUNT(*) as month_total_reports')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as month_resolved_reports', [Report::STATUS_RESOLVED])
            ->first();

        $monthlyTotalReports = (int) ($monthlyCounts->month_total_reports ?? 0);
        $monthlyResolvedReports = (int) ($monthlyCounts->month_resolved_reports ?? 0);
        $resolutionRate = $monthlyTotalReports > 0
            ? (int) round(($monthlyResolvedReports / $monthlyTotalReports) * 100)
            : 0;

        $recentActivity = Report::query()
            ->with('category')
            ->where('user_id', $userId)
            ->orderByDesc('updated_at')
            ->take(5)
            ->get();

        return view('user.dashboard', compact(
            'totalReports',
            'pendingReports',
            'approvedReports',
            'rejectedReports',
            'underReviewReports',
            'resolvedReports',
            'attentionReports',
            'monthlyTotalReports',
            'monthlyResolvedReports',
            'resolutionRate',
            'recentActivity'
        ));
    }
}
