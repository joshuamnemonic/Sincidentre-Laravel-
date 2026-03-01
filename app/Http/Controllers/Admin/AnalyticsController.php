<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Report;
use App\Models\Activity;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $admin = Auth::user();
        $departmentId = $admin->department_id;

        // Determine date range based on period
        $period = $request->get('period', 'month');
        $dateRange = $this->getDateRange($period, $request);

        // Get reports for the department
        $baseQuery = Report::whereHas('user', function($q) use ($departmentId) {
            $q->where('department_id', $departmentId);
        });

        // Total reports (all time)
        $totalReports = $baseQuery->count();

        // Reports in current period
        $periodReports = (clone $baseQuery)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        // Reports in previous period (for comparison)
        $previousPeriodStart = $dateRange['start']->copy()->sub($dateRange['start']->diffInDays($dateRange['end']), 'days');
        $previousPeriodReports = (clone $baseQuery)
            ->whereBetween('created_at', [$previousPeriodStart, $dateRange['start']])
            ->count();

        // Calculate percentage change
        $percentageChange = $previousPeriodReports > 0 
            ? (($periodReports - $previousPeriodReports) / $previousPeriodReports) * 100 
            : 0;

        // Average response time (time from creation to first status change)
        $avgResponseTime = $this->getAverageResponseTime($departmentId);

        // Resolution rate
        $resolvedCount = (clone $baseQuery)->where('status', 'Resolved')->count();
        $resolutionRate = $totalReports > 0 ? round(($resolvedCount / $totalReports) * 100, 1) : 0;

        // 🚨 ALERTS - Recurring Incidents Detection
        $alerts = $this->generateAlerts($departmentId, $dateRange);

        // Reports over time (trend chart)
        $reportsOverTime = $this->getReportsOverTime($departmentId, $dateRange, $period);

        // Reports by category
        $categoryData = $this->getCategoryData($departmentId, $dateRange);

        // Reports by status
        $statusData = $this->getStatusData($departmentId, $dateRange);

        // Reports by day of week
        $dayOfWeekData = $this->getDayOfWeekData($departmentId, $dateRange);

        // Top categories table
        $topCategories = $this->getTopCategories($departmentId, $dateRange);

        // Frequent reporters (users with multiple reports)
        $frequentReporters = $this->getFrequentReporters($departmentId, $dateRange);

        // Location hotspots
        $locationHotspots = $this->getLocationHotspots($departmentId, $dateRange);

        // Performance metrics
        $avgTimeToApprove = $this->getAverageTimeToApprove($departmentId);
        $avgTimeToResolve = $this->getAverageTimeToResolve($departmentId);
        $overdueReports = $this->getOverdueReports($departmentId);
        
        // Most active admin
        $mostActiveAdminData = $this->getMostActiveAdmin($departmentId, $dateRange);
        $mostActiveAdmin = $mostActiveAdminData['name'];
        $mostActiveAdminCount = $mostActiveAdminData['count'];

        return view('admin.analytics', compact(
            'totalReports',
            'periodReports',
            'percentageChange',
            'avgResponseTime',
            'resolutionRate',
            'alerts',
            'reportsOverTime',
            'categoryData',
            'statusData',
            'dayOfWeekData',
            'topCategories',
            'frequentReporters',
            'locationHotspots',
            'avgTimeToApprove',
            'avgTimeToResolve',
            'overdueReports',
            'mostActiveAdmin',
            'mostActiveAdminCount'
        ));
    }

    private function getDateRange($period, $request)
    {
        // Check for custom date range first
        if ($request->filled('custom_from') && $request->filled('custom_to')) {
            return [
                'start' => Carbon::parse($request->custom_from)->startOfDay(),
                'end' => Carbon::parse($request->custom_to)->endOfDay(),
            ];
        }

        // Predefined periods
        $end = Carbon::now();
        
        switch ($period) {
            case 'week':
                $start = Carbon::now()->subDays(7);
                break;
            case 'quarter':
                $start = Carbon::now()->subMonths(3);
                break;
            case 'year':
                $start = Carbon::now()->subYear();
                break;
            case 'all':
                $start = Carbon::parse('2020-01-01'); // Or your system start date
                break;
            case 'month':
            default:
                $start = Carbon::now()->subDays(30);
                break;
        }

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    private function getAverageResponseTime($departmentId)
    {
        $reports = Report::whereHas('user', fn($q) => $q->where('department_id', $departmentId))
            ->whereNotNull('handled_by')
            ->get();

        if ($reports->isEmpty()) {
            return 'N/A';
        }

        $totalHours = 0;
        $count = 0;

        foreach ($reports as $report) {
            $firstActivity = Activity::where('report_id', $report->id)
                ->orderBy('created_at', 'asc')
                ->first();

            if ($firstActivity) {
                $hours = $report->created_at->diffInHours($firstActivity->created_at);
                $totalHours += $hours;
                $count++;
            }
        }

        return $count > 0 ? round($totalHours / $count, 1) : 'N/A';
    }

    private function generateAlerts($departmentId, $dateRange)
    {
        $alerts = [];

        // 1. Frequent reporters alert (users with 3+ reports in period)
        $frequentReporters = Report::select('user_id', DB::raw('COUNT(*) as report_count'))
            ->whereHas('user', fn($q) => $q->where('department_id', $departmentId))
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('user_id')
            ->having('report_count', '>=', 3)
            ->with('user')
            ->get();

        foreach ($frequentReporters as $reporter) {
            if ($reporter->user) {
                $alerts[] = [
                    'title' => 'Frequent Reporter Detected',
                    'message' => "{$reporter->user->first_name} {$reporter->user->last_name} has submitted {$reporter->report_count} reports in this period. May need support or intervention.",
                    'link' => route('admin.reports') . '?reporter=' . urlencode($reporter->user->first_name)
                ];
            }
        }

        // 2. Location hotspot alert (5+ incidents in same location)
        $locationHotspots = Report::select('location', DB::raw('COUNT(*) as incident_count'))
            ->whereHas('user', fn($q) => $q->where('department_id', $departmentId))
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->groupBy('location')
            ->having('incident_count', '>=', 5)
            ->get();

        foreach ($locationHotspots as $hotspot) {
            $alerts[] = [
                'title' => 'Location Hotspot Alert',
                'message' => "{$hotspot->location} has {$hotspot->incident_count} incidents reported. Consider increasing monitoring or security in this area.",
                'link' => route('admin.reports')
            ];
        }

        // 3. Category spike alert (category increased by 50%+ compared to previous period)
        $currentCategoryCounts = Report::select('category_id', DB::raw('COUNT(*) as count'))
            ->whereHas('user', fn($q) => $q->where('department_id', $departmentId))
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('category_id')
            ->pluck('count', 'category_id');

        $previousPeriodStart = $dateRange['start']->copy()->sub($dateRange['start']->diffInDays($dateRange['end']), 'days');
        $previousCategoryCounts = Report::select('category_id', DB::raw('COUNT(*) as count'))
            ->whereHas('user', fn($q) => $q->where('department_id', $departmentId))
            ->whereBetween('created_at', [$previousPeriodStart, $dateRange['start']])
            ->groupBy('category_id')
            ->pluck('count', 'category_id');

        foreach ($currentCategoryCounts as $categoryId => $currentCount) {
            $previousCount = $previousCategoryCounts[$categoryId] ?? 0;
            
            if ($previousCount > 0) {
                $increase = (($currentCount - $previousCount) / $previousCount) * 100;
                
                if ($increase >= 50) {
                    $category = Category::find($categoryId);
                    if ($category) {
                        $alerts[] = [
                            'title' => 'Category Spike Detected',
                            'message' => "{$category->name} reports increased by " . round($increase, 1) . "% compared to previous period ({$previousCount} → {$currentCount}).",
                            'link' => route('admin.reports') . '?category=' . $categoryId
                        ];
                    }
                }
            }
        }

        // 4. Overdue reports alert
        $overdueCount = Report::whereHas('user', fn($q) => $q->where('department_id', $departmentId))
            ->whereIn('status', ['Pending', 'Under Review', 'Approved'])
            ->where('created_at', '<', Carbon::now()->subDays(3))
            ->count();

        if ($overdueCount > 0) {
            $alerts[] = [
                'title' => 'Overdue Reports',
                'message' => "{$overdueCount} reports have been pending for more than 3 days. Immediate action required.",
                'link' => route('admin.reports') . '?status=pending'
            ];
        }

        return $alerts;
    }

    private function getReportsOverTime($departmentId, $dateRange, $period)
    {
        $reports = Report::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->whereHas('user', fn($q) => $q->where('department_id', $departmentId))
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $data = [];

        // Generate date range
        $start = $dateRange['start']->copy();
        $end = $dateRange['end']->copy();

        while ($start <= $end) {
            $dateStr = $start->format('Y-m-d');
            $labels[] = $start->format('M d');
            
            $reportForDate = $reports->firstWhere('date', $dateStr);
            $data[] = $reportForDate ? $reportForDate->count : 0;
            
            $start->addDay();
        }

        return [
            'labels' => $labels,
            'data' => $data
        ];
    }

    private function getCategoryData($departmentId, $dateRange)
    {
        $categories = Report::select('categories.name as category_name', DB::raw('COUNT(*) as count'))
            ->join('categories', 'reports.category_id', '=', 'categories.id')
            ->whereHas('user', fn($q) => $q->where('department_id', $departmentId))
            ->whereBetween('reports.created_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('categories.id', 'categories.name')
            ->get();

        return [
            'labels' => $categories->pluck('category_name')->toArray(),
            'data' => $categories->pluck('count')->toArray()
        ];
    }

    private function getStatusData($departmentId, $dateRange)
    {
        $statuses = Report::select('status', DB::raw('COUNT(*) as count'))
            ->whereHas('user', fn($q) => $q->where('department_id', $departmentId))
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('status')
            ->get();

        return [
            'labels' => $statuses->pluck('status')->toArray(),
            'data' => $statuses->pluck('count')->toArray()
        ];
    }

    private function getDayOfWeekData($departmentId, $dateRange)
    {
        $reports = Report::select(DB::raw('DAYOFWEEK(created_at) as day'), DB::raw('COUNT(*) as count'))
            ->whereHas('user', fn($q) => $q->where('department_id', $departmentId))
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $data = [];

        for ($i = 1; $i <= 7; $i++) {
            $data[] = isset($reports[$i]) ? $reports[$i]->count : 0;
        }

        return [
            'labels' => $days,
            'data' => $data
        ];
    }

    private function getTopCategories($departmentId, $dateRange)
    {
        return Report::select('categories.name as category_name', DB::raw('COUNT(*) as total'))
            ->join('categories', 'reports.category_id', '=', 'categories.id')
            ->whereHas('user', fn($q) => $q->where('department_id', $departmentId))
            ->whereBetween('reports.created_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();
    }

    private function getFrequentReporters($departmentId, $dateRange)
    {
        return Report::select(
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) as reporter_name"),
                DB::raw('COUNT(*) as report_count')
            )
            ->join('users', 'reports.user_id', '=', 'users.id')
            ->where('users.department_id', $departmentId)
            ->whereBetween('reports.created_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('reports.user_id', 'users.first_name', 'users.last_name')
            ->having('report_count', '>', 1)
            ->orderByDesc('report_count')
            ->limit(10)
            ->get();
    }

    private function getLocationHotspots($departmentId, $dateRange)
    {
        return Report::select('location', DB::raw('COUNT(*) as incident_count'))
            ->whereHas('user', fn($q) => $q->where('department_id', $departmentId))
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->groupBy('location')
            ->orderByDesc('incident_count')
            ->limit(10)
            ->get();
    }

    private function getAverageTimeToApprove($departmentId)
    {
        $approvedReports = Report::whereHas('user', fn($q) => $q->where('department_id', $departmentId))
            ->where('status', 'Approved')
            ->get();

        if ($approvedReports->isEmpty()) {
            return 'N/A';
        }

        $totalHours = 0;
        $count = 0;

        foreach ($approvedReports as $report) {
            $approvalActivity = Activity::where('report_id', $report->id)
                ->where('action', 'Report Approved')
                ->first();

            if ($approvalActivity) {
                $hours = $report->created_at->diffInHours($approvalActivity->created_at);
                $totalHours += $hours;
                $count++;
            }
        }

        return $count > 0 ? round($totalHours / $count, 1) : 'N/A';
    }

    private function getAverageTimeToResolve($departmentId)
    {
        $resolvedReports = Report::whereHas('user', fn($q) => $q->where('department_id', $departmentId))
            ->where('status', 'Resolved')
            ->get();

        if ($resolvedReports->isEmpty()) {
            return 'N/A';
        }

        $totalDays = 0;
        $count = 0;

        foreach ($resolvedReports as $report) {
            $resolutionActivity = Activity::where('report_id', $report->id)
                ->where('new_status', 'Resolved')
                ->first();

            if ($resolutionActivity) {
                $days = $report->created_at->diffInDays($resolutionActivity->created_at);
                $totalDays += $days;
                $count++;
            }
        }

        return $count > 0 ? round($totalDays / $count, 1) : 'N/A';
    }

    private function getOverdueReports($departmentId)
    {
        return Report::whereHas('user', fn($q) => $q->where('department_id', $departmentId))
            ->whereIn('status', ['Pending', 'Under Review', 'Approved'])
            ->where('created_at', '<', Carbon::now()->subDays(3))
            ->count();
    }

    private function getMostActiveAdmin($departmentId, $dateRange)
    {
        $mostActive = Activity::select('performed_by', DB::raw('COUNT(*) as action_count'))
            ->whereHas('report.user', fn($q) => $q->where('department_id', $departmentId))
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->whereNotNull('performed_by')
            ->groupBy('performed_by')
            ->orderByDesc('action_count')
            ->first();

        if ($mostActive) {
            $admin = User::find($mostActive->performed_by);
            return [
                'name' => $admin ? "{$admin->first_name} {$admin->last_name}" : 'Unknown',
                'count' => $mostActive->action_count
            ];
        }

        return [
            'name' => 'N/A',
            'count' => 0
        ];
    }

    public function export(Request $request)
    {
        $format = $request->get('format', 'csv');
        $period = $request->get('period', 'month');
        
        $admin = Auth::user();
        $departmentId = $admin->department_id;
        $dateRange = $this->getDateRange($period, $request);

        $reports = Report::with(['user', 'category'])
            ->whereHas('user', fn($q) => $q->where('department_id', $departmentId))
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->get();

        if ($format === 'csv') {
            return $this->exportCSV($reports);
        } elseif ($format === 'pdf') {
            return $this->exportPDF($reports, $dateRange);
        }

        return redirect()->back();
    }

    private function exportCSV($reports)
    {
        $filename = 'analytics_export_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($reports) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                'Report ID',
                'Title',
                'Category',
                'Reporter',
                'Location',
                'Status',
                'Created At',
                'Resolved At'
            ]);

            // Data
            foreach ($reports as $report) {
                fputcsv($file, [
                    $report->id,
                    $report->title,
                    $report->category->name ?? 'N/A',
                    ($report->user->first_name ?? '') . ' ' . ($report->user->last_name ?? ''),
                    $report->location ?? 'N/A',
                    $report->status,
                    $report->created_at->format('Y-m-d H:i:s'),
                    $report->status === 'Resolved' ? $report->updated_at->format('Y-m-d H:i:s') : 'N/A'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportPDF($reports, $dateRange)
    {
        // This is a placeholder - you would typically use a PDF library like DomPDF or TCPDF
        // For now, we'll return a simple text response
        
        $content = "Analytics Report\n";
        $content .= "Period: " . $dateRange['start']->format('Y-m-d') . " to " . $dateRange['end']->format('Y-m-d') . "\n";
        $content .= "Total Reports: " . $reports->count() . "\n\n";
        
        foreach ($reports as $report) {
            $content .= "#{$report->id} - {$report->title} - {$report->status}\n";
        }

        return response($content, 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="analytics_' . date('Y-m-d') . '.txt"'
        ]);
    }
}