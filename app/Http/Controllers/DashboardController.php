<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Report;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Current user ID
        $userId = Auth::id();

        // Search keyword
        $search = $request->input('search');

        // Query reports for the logged-in user only
        $reportsQuery = Report::where('user_id', $userId)
                              ->orderBy('submitted_at', 'desc');

        if ($search) {
            $reportsQuery->where(function($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('status', 'like', "%{$search}%");
            });
        }

        // Latest 5 (or filtered) reports
        $recentReports = $reportsQuery->take(5)->get();

        // Counts for this user
        $totalReports    = Report::where('user_id', $userId)->count();
        $pendingReports  = Report::where('user_id', $userId)->whereRaw('LOWER(status) = ?', ['pending'])->count();
        $resolvedReports = Report::where('user_id', $userId)->whereRaw('LOWER(status) = ?', ['resolved'])->count();
        $rejectedReports = Report::where('user_id', $userId)->whereRaw('LOWER(status) = ?', ['rejected'])->count();
        $approvedReports = Report::where('user_id', $userId)->whereRaw('LOWER(status) = ?', ['approved'])->count();
        $underReviewReports = Report::where('user_id', $userId)->whereRaw('LOWER(status) = ?', ['under review'])->count();

        return view('user.dashboard', compact(
            'totalReports',
            'pendingReports',
            'resolvedReports',
            'recentReports',
            'rejectedReports',
            'approvedReports',
            'underReviewReports',
            'search'
        ));
    }
}
