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

        $search = trim((string) $request->input('search', ''));
        $status = $request->filled('status') ? Report::normalizeStatus((string) $request->input('status')) : null;

        $reportsQuery = Report::query()
            ->with('category')
            ->where('user_id', $userId);

        if ($search !== '') {
            $reportsQuery->where(function ($query) use ($search) {
                $query->where('description', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('category', function ($categoryQuery) use ($search) {
                        $categoryQuery->where('name', 'like', "%{$search}%");
                    });

                if (ctype_digit($search)) {
                    $query->orWhere('id', (int) $search);
                }
            });
        }

        if (!empty($status)) {
            $reportsQuery->where('status', $status);
        }

        $recentReports = $reportsQuery
            ->orderByRaw('COALESCE(submitted_at, created_at) DESC')
            ->take(6)
            ->get();

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

        return view('user.dashboard', compact(
            'totalReports',
            'pendingReports',
            'approvedReports',
            'rejectedReports',
            'underReviewReports',
            'resolvedReports',
            'recentReports',
            'search',
            'status'
        ));
    }
}
