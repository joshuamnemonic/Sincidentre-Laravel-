<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Activity;
use Illuminate\Support\Facades\Auth;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $admin = Auth::user();
        $departmentId = $admin->department_id;

        // Query activities only from reports in the same department
        $query = Activity::with(['performedBy', 'admin', 'report.user'])
            ->whereHas('report.user', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });

        // FILTER: Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('remarks', 'like', "%{$search}%")
                  ->orWhere('report_id', 'like', "%{$search}%")
                  ->orWhereHas('performedBy', function($pq) use ($search) {
                      $pq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('admin', function($aq) use ($search) {
                      $aq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        // FILTER: Action type
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // FILTER: Date range
        if ($request->filled('from') && $request->filled('to')) {
            $query->whereBetween('created_at', [
                $request->from . ' 00:00:00',
                $request->to . ' 23:59:59'
            ]);
        } elseif ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        } elseif ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        // Get paginated activities
        $activities = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.activitylogs', compact('activities'));
    }
}