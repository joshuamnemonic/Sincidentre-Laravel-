<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        // ✅ Count users in department including DSDO (exclude Top Management)
        $departments = Department::withCount([
            'users as regular_users_count' => function ($query) {
                $query->where('is_top_management', 0);
            }
        ])
        ->orderBy('name')
        ->get();

        return view('admin.departments', compact('departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:departments,name',
        ]);

        Department::create($validated);

        return redirect()
            ->route('admin.departments.index')
            ->with('success', 'Department added successfully!');
    }

}

