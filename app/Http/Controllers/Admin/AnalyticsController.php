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

        // Determine date range based on period
        $period = $request->get('period', 'month');
        $dateRange = $this->getDateRange($period, $request);

        // Get reports visible to the DSDO (department-based + assigned reports)
        $baseQuery = $this->buildVisibleReportsQuery($admin);

        // Total reports (all time)
        $totalReports = $baseQuery->count();

        // Reports in current period
        $periodReports = (clone $baseQuery)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        // Reports in previous period (for comparison)
        $previousPeriodStart = $dateRange['start']->copy()->subDays($dateRange['start']->diffInDays($dateRange['end']));
        $previousPeriodReports = (clone $baseQuery)
            ->whereBetween('created_at', [$previousPeriodStart, $dateRange['start']])
            ->count();

        // Calculate percentage change
        $percentageChange = $previousPeriodReports > 0 
            ? (($periodReports - $previousPeriodReports) / $previousPeriodReports) * 100 
            : 0;

        // Average response time (time from creation to first status change)
        $avgResponseTime = $this->getAverageResponseTime($baseQuery);

        // Resolution rate
        $resolvedCount = (clone $baseQuery)->where('status', Report::STATUS_RESOLVED)->count();
        $resolutionRate = $totalReports > 0 ? round(($resolvedCount / $totalReports) * 100, 1) : 0;

        // 🚨 ALERTS - Recurring Incidents Detection
        $alerts = $this->generateAlerts($baseQuery, $dateRange);

        // Reports over time (trend chart)
        $reportsOverTime = $this->getReportsOverTime($baseQuery, $dateRange, $period);

        // Reports by category
        $categoryData = $this->getCategoryData($baseQuery, $dateRange);

        // Reports by status
        $statusData = $this->getStatusData($baseQuery, $dateRange);

        // Reports by day of week
        $dayOfWeekData = $this->getDayOfWeekData($baseQuery, $dateRange);

        // Top categories table
        $topCategories = $this->getTopCategories($baseQuery, $dateRange);

        // Frequent reporters (users with multiple reports)
        $frequentReporters = $this->getFrequentReporters($baseQuery, $dateRange);

        // Location hotspots
        $locationHotspots = $this->getLocationHotspots($baseQuery, $dateRange);
        $locationDetailsMap = $this->getLocationHotspotDetailsMap($baseQuery, $dateRange, $locationHotspots);
        $selectedLocation = trim((string) $request->get('location', ''));
        $locationHotspotDetails = $selectedLocation !== ''
            ? ($locationDetailsMap[$selectedLocation] ?? collect())
            : collect();

        // Performance metrics
        $avgTimeToApprove = $this->getAverageTimeToApprove($baseQuery);
        $avgTimeToResolve = $this->getAverageTimeToResolve($baseQuery);
        $overdueReports = $this->getOverdueReports($baseQuery);
        
        // Most active admin
        $mostActiveAdminData = $this->getMostActiveAdmin($baseQuery, $dateRange);
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
            'locationHotspotDetails',
            'locationDetailsMap',
            'selectedLocation',
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

    private function buildVisibleReportsQuery(User $admin)
    {
        $departmentId = $admin->department_id;
        $fullName = strtolower(trim((string) (($admin->first_name ?? '') . ' ' . ($admin->last_name ?? ''))));

        return Report::query()->where(function ($query) use ($departmentId, $fullName) {
            if ($departmentId) {
                $query->where(function ($deptQuery) use ($departmentId) {
                    $deptQuery->whereHas('user', function ($userQuery) use ($departmentId) {
                        $userQuery->where('department_id', $departmentId);
                    })->whereHas('category', function ($categoryQuery) {
                        $categoryQuery->whereNotIn('classification', ['Major', 'Grave']);
                    });
                });
            }

            if ($fullName !== '') {
                if ($departmentId) {
                    $query->orWhereRaw('LOWER(assigned_to) = ?', [$fullName]);
                } else {
                    $query->whereRaw('LOWER(assigned_to) = ?', [$fullName]);
                }
            }

            if (!$departmentId && $fullName === '') {
                $query->whereRaw('1 = 0');
            }
        });
    }

    private function getAverageResponseTime($baseQuery)
    {
        $reports = (clone $baseQuery)
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

    private function generateAlerts($baseQuery, $dateRange)
    {
        $alerts = [];

        // 1. Frequent reporters alert (users with 3+ reports in period)
        $frequentReporters = (clone $baseQuery)
            ->select('user_id', DB::raw('COUNT(*) as report_count'))
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
                    'link' => route('admin.users.show', $reporter->user_id)
                ];
            }
        }

        // 2. Location hotspot alert (5+ incidents in same location)
        $locationHotspots = (clone $baseQuery)
            ->select('location', DB::raw('COUNT(*) as incident_count'))
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
        $currentCategoryCounts = (clone $baseQuery)
            ->select('category_id', DB::raw('COUNT(*) as count'))
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('category_id')
            ->pluck('count', 'category_id');

        $previousPeriodStart = $dateRange['start']->copy()->subDays($dateRange['start']->diffInDays($dateRange['end']));
        $previousCategoryCounts = (clone $baseQuery)
            ->select('category_id', DB::raw('COUNT(*) as count'))
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
        $overdueCount = (clone $baseQuery)
            ->whereIn('status', [
                Report::STATUS_PENDING,
                Report::STATUS_UNDER_REVIEW,
                Report::STATUS_APPROVED,
            ])
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

    private function getReportsOverTime($baseQuery, $dateRange, $period)
    {
        $reports = (clone $baseQuery)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
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

    private function getCategoryData($baseQuery, $dateRange)
    {
        $categories = (clone $baseQuery)
            ->join('categories', 'reports.category_id', '=', 'categories.id')
            ->select('categories.name as category_name', DB::raw('COUNT(*) as count'))
            ->whereBetween('reports.created_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('categories.id', 'categories.name')
            ->get();

        return [
            'labels' => $categories->pluck('category_name')->toArray(),
            'data' => $categories->pluck('count')->toArray()
        ];
    }

    private function getStatusData($baseQuery, $dateRange)
    {
        $statusRows = (clone $baseQuery)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('status')
            ->get();

        $orderedStatuses = [
            Report::STATUS_PENDING,
            Report::STATUS_APPROVED,
            Report::STATUS_REJECTED,
            Report::STATUS_UNDER_REVIEW,
            Report::STATUS_RESOLVED,
        ];

        $countsByStatus = [];
        foreach ($statusRows as $row) {
            $normalized = Report::normalizeStatus($row->status);
            $countsByStatus[$normalized] = ($countsByStatus[$normalized] ?? 0) + (int) $row->count;
        }

        $labels = [];
        $data = [];
        foreach ($orderedStatuses as $status) {
            $labels[] = Report::labelForStatus($status);
            $data[] = (int) ($countsByStatus[$status] ?? 0);
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    private function getDayOfWeekData($baseQuery, $dateRange)
    {
        $reports = (clone $baseQuery)
            ->select(DB::raw('DAYOFWEEK(created_at) as day'), DB::raw('COUNT(*) as count'))
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

    private function getTopCategories($baseQuery, $dateRange)
    {
        return (clone $baseQuery)
            ->join('categories', 'reports.category_id', '=', 'categories.id')
            ->select('categories.name as category_name', DB::raw('COUNT(*) as total'))
            ->whereBetween('reports.created_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();
    }

    private function getFrequentReporters($baseQuery, $dateRange)
    {
        return (clone $baseQuery)->select(
                'reports.user_id',
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) as reporter_name"),
                DB::raw('COUNT(*) as report_count')
            )
            ->join('users', 'reports.user_id', '=', 'users.id')
            ->whereBetween('reports.created_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('reports.user_id', 'users.first_name', 'users.last_name')
            ->having('report_count', '>', 1)
            ->orderByDesc('report_count')
            ->limit(10)
            ->get();
    }

    private function getLocationHotspots($baseQuery, $dateRange)
    {
        return (clone $baseQuery)
            ->select('location', DB::raw('COUNT(*) as incident_count'))
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->groupBy('location')
            ->orderByDesc('incident_count')
            ->limit(10)
            ->get();
    }

    private function getLocationHotspotDetails($baseQuery, $dateRange, $location)
    {
        $location = trim((string) $location);

        if ($location === '') {
            return collect();
        }

        $detailCounts = (clone $baseQuery)
            ->select('location_details')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->where('location', $location)
            ->get()
            ->map(function ($report) {
                $detailLabel = trim((string) ($report->location_details ?? ''));

                return $detailLabel === '' ? 'No details provided' : $detailLabel;
            })
            ->countBy();

        return $detailCounts
            ->sortDesc()
            ->map(function ($count, $detailLabel) {
                return (object) [
                    'detail_label' => $detailLabel,
                    'detail_count' => $count,
                ];
            })
            ->values();
    }

    private function getLocationHotspotDetailsMap($baseQuery, $dateRange, $locationHotspots)
    {
        $locations = collect($locationHotspots)
            ->pluck('location')
            ->filter(fn($location) => trim((string) $location) !== '')
            ->values();

        if ($locations->isEmpty()) {
            return [];
        }

        $rows = (clone $baseQuery)
            ->select('location', 'location_details', DB::raw('COUNT(*) as detail_count'))
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->whereIn('location', $locations)
            ->groupBy('location', 'location_details')
            ->orderByDesc('detail_count')
            ->get();

        $detailsMap = [];
        foreach ($locations as $location) {
            $detailsMap[$location] = collect();
        }

        foreach ($rows as $row) {
            $location = (string) $row->location;
            $label = trim((string) ($row->location_details ?? ''));
            $detailLabel = $label === '' ? 'No details provided' : $label;

            $detailsMap[$location] = $detailsMap[$location]->push((object) [
                'detail_label' => $detailLabel,
                'detail_count' => (int) $row->detail_count,
            ]);
        }

        return $detailsMap;
    }

    private function getAverageTimeToApprove($baseQuery)
    {
        $approvedReports = (clone $baseQuery)
            ->where('status', Report::STATUS_APPROVED)
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

    private function getAverageTimeToResolve($baseQuery)
    {
        $resolvedReports = (clone $baseQuery)
            ->where('status', Report::STATUS_RESOLVED)
            ->get();

        if ($resolvedReports->isEmpty()) {
            return 'N/A';
        }

        $totalDays = 0;
        $count = 0;

        foreach ($resolvedReports as $report) {
            $resolutionActivity = Activity::where('report_id', $report->id)
                ->where('new_status', Report::STATUS_RESOLVED)
                ->first();

            if ($resolutionActivity) {
                $days = $report->created_at->diffInDays($resolutionActivity->created_at);
                $totalDays += $days;
                $count++;
            }
        }

        return $count > 0 ? round($totalDays / $count, 1) : 'N/A';
    }

    private function getOverdueReports($baseQuery)
    {
        return (clone $baseQuery)
            ->whereIn('status', [
                Report::STATUS_PENDING,
                Report::STATUS_UNDER_REVIEW,
                Report::STATUS_APPROVED,
            ])
            ->where('created_at', '<', Carbon::now()->subDays(3))
            ->count();
    }

    private function getMostActiveAdmin($baseQuery, $dateRange)
    {
        $mostActive = Activity::select('performed_by', DB::raw('COUNT(*) as action_count'))
            ->whereIn('report_id', (clone $baseQuery)->select('id'))
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
        $dateRange = $this->getDateRange($period, $request);

        $baseQuery = $this->buildVisibleReportsQuery($admin);

        $totalReports = (clone $baseQuery)->count();
        $periodReports = (clone $baseQuery)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        $statusData = $this->getStatusData($baseQuery, $dateRange);
        $topCategories = $this->getTopCategories($baseQuery, $dateRange);
        $locationHotspots = $this->getLocationHotspots($baseQuery, $dateRange);
        $overdueReports = $this->getOverdueReports($baseQuery);

        $analyticsSnapshot = [
            'period_start' => $dateRange['start']->format('Y-m-d'),
            'period_end' => $dateRange['end']->format('Y-m-d'),
            'total_reports_all_time' => $totalReports,
            'reports_in_selected_period' => $periodReports,
            'overdue_reports' => $overdueReports,
            'status_data' => $statusData,
            'top_categories' => $topCategories,
            'location_hotspots' => $locationHotspots,
        ];

        if ($format === 'csv') {
            return $this->exportCSV($analyticsSnapshot);
        } elseif ($format === 'pdf') {
            return $this->exportPDF($analyticsSnapshot);
        }

        return redirect()->back();
    }

    private function exportCSV(array $snapshot)
    {
        $filename = 'analytics_export_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($snapshot) {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['Section', 'Key', 'Value']);
            fputcsv($file, ['Overview', 'Period Start', $snapshot['period_start']]);
            fputcsv($file, ['Overview', 'Period End', $snapshot['period_end']]);
            fputcsv($file, ['Overview', 'Total Reports (All Time)', $snapshot['total_reports_all_time']]);
            fputcsv($file, ['Overview', 'Reports in Selected Period', $snapshot['reports_in_selected_period']]);
            fputcsv($file, ['Overview', 'Overdue Reports (>3 days)', $snapshot['overdue_reports']]);

            fputcsv($file, []);
            fputcsv($file, ['Status Distribution', 'Status', 'Count']);
            foreach (($snapshot['status_data']['labels'] ?? []) as $index => $label) {
                $count = $snapshot['status_data']['data'][$index] ?? 0;
                fputcsv($file, ['Status Distribution', $label, $count]);
            }

            fputcsv($file, []);
            fputcsv($file, ['Top Categories', 'Category', 'Count']);
            foreach ($snapshot['top_categories'] as $category) {
                fputcsv($file, ['Top Categories', $category->category_name, $category->total]);
            }

            fputcsv($file, []);
            fputcsv($file, ['Location Hotspots', 'Location', 'Incidents']);
            foreach ($snapshot['location_hotspots'] as $hotspot) {
                fputcsv($file, ['Location Hotspots', $hotspot->location, $hotspot->incident_count]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportPDF(array $snapshot)
    {
        // This is a placeholder - you would typically use a PDF library like DomPDF or TCPDF
        // For now, we'll return a simple text response

        $content = "Analytics & Insights Summary\n";
        $content .= "Period: {$snapshot['period_start']} to {$snapshot['period_end']}\n";
        $content .= "Total Reports (All Time): {$snapshot['total_reports_all_time']}\n";
        $content .= "Reports in Selected Period: {$snapshot['reports_in_selected_period']}\n";
        $content .= "Overdue Reports (>3 days): {$snapshot['overdue_reports']}\n\n";

        $content .= "Status Distribution:\n";
        foreach (($snapshot['status_data']['labels'] ?? []) as $index => $label) {
            $count = $snapshot['status_data']['data'][$index] ?? 0;
            $content .= "- {$label}: {$count}\n";
        }

        $content .= "\nTop Categories:\n";
        foreach ($snapshot['top_categories'] as $category) {
            $content .= "- {$category->category_name}: {$category->total}\n";
        }

        $content .= "\nLocation Hotspots:\n";
        foreach ($snapshot['location_hotspots'] as $hotspot) {
            $content .= "- {$hotspot->location}: {$hotspot->incident_count}\n";
        }

        return response($content, 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="analytics_' . date('Y-m-d') . '.txt"'
        ]);
    }
}
