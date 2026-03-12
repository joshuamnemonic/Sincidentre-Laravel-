<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Report;
use Illuminate\Support\Facades\Auth;

class MyReportsController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        $search = $request->input('search');
        $status = $request->input('status');

        $query = Report::with('category')
                   ->where('user_id', $userId);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->whereRaw('LOWER(status) = ?', [strtolower($status)]);
        }

        $myReports = $query->orderByRaw('COALESCE(submitted_at, created_at) DESC')->get();

        return view('user.myreports', compact('myReports', 'search', 'status'));
    }
}
