<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Report;
use Illuminate\Support\Facades\Auth;

class MyReportsController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        // Fetch only reports of the logged-in user
        $myReports = Report::where('user_id', $userId)
                           ->orderBy('submitted_at', 'desc')
                           ->get();

        return view('user.myreports', compact('myReports'));
    }
}
