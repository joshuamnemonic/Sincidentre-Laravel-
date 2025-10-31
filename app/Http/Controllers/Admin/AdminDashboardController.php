<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;

class AdminDashboardController extends Controller
{
    public function index()
    {
        return view('admin.admindashboard', [
            'totalReports'    => Report::count(),
            'pendingReports'  => Report::where('status', 'Pending')->count(),
            'underReview'     => Report::where('status', 'Under Review')->count(),
            'resolvedReports' => Report::where('status', 'Resolved')->count(),
            'recentReports'   => Report::latest()->take(10)->get(),
            'totalUsers'      => User::count(),
        ]);
    }
}
