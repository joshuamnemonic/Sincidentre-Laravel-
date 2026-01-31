<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\Request;

class ActivityLogController extends Controller  // ✅ Singular
{
    public function index(Request $request)
    {
        $search = $request->get('search');

        $activities = Activity::with(['report', 'admin', 'user']) // ✅ Eager load
            ->where('action', '!=', 'Report Submitted') // ✅ Exclude user submissions
            ->when($search, function($query, $search) {
                $query->where('action', 'like', "%{$search}%")
                      ->orWhere('remarks', 'like', "%{$search}%")
                      ->orWhereHas('admin', function($q) use ($search) {
                          $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                      });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.activitylogs', compact('activities'));
    }
}