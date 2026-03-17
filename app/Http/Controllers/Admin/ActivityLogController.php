<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Activity;
use App\Models\Department;
use Illuminate\Support\Facades\Auth;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $admin = Auth::user();
        $query = Activity::with(['performedBy', 'admin', 'report.user.department', 'report.category']);

        if (!(bool) $admin->is_top_management) {
            $departmentId = $admin->department_id;
            $query->whereHas('report.user', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            })->whereHas('report.category', function ($q) {
                $q->whereNotIn('classification', ['Major', 'Grave']);
            });
        } else {
            $positionCode = trim((string) ($admin->routing_position_code ?? ''));
            $fullName = strtolower(trim((string) (($admin->first_name ?? '') . ' ' . ($admin->last_name ?? ''))));

            $query->whereHas('report', function ($q) use ($positionCode, $fullName) {
                if ($positionCode !== '') {
                    $q->where('assigned_position_code', $positionCode);
                }

                if ($fullName !== '') {
                    if ($positionCode !== '') {
                        $q->orWhereRaw('LOWER(assigned_to) = ?', [$fullName]);
                    } else {
                        $q->whereRaw('LOWER(assigned_to) = ?', [$fullName]);
                    }
                }

                if ($positionCode === '' && $fullName === '') {
                    $q->whereRaw('1 = 0');
                }
            });
        }

        if ((bool) $admin->is_top_management && $request->filled('department')) {
            $departmentId = (int) $request->department;
            $query->whereHas('report.user', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

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

        // FILTER: Current report status
        if ($request->filled('status')) {
            $query->whereHas('report', function ($q) use ($request) {
                $q->whereRaw('LOWER(status) = ?', [strtolower((string) $request->status)]);
            });
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

        $actions = Activity::query()
            ->select('action')
            ->whereNotNull('action')
            ->where('action', '!=', '')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $departments = (bool) $admin->is_top_management
            ? Department::query()->orderBy('name')->get(['id', 'name'])
            : collect();

        $statuses = ['Pending', 'Approved', 'Rejected', 'Under Review', 'Resolved'];

        $isTopManagement = (bool) $admin->is_top_management;

        return view('admin.activitylogs', compact('activities', 'actions', 'departments', 'statuses', 'isTopManagement'));
    }
}