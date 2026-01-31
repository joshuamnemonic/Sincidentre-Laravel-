<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        // ✅ Count only regular users (where is_admin = 0)
        $departments = Department::withCount([
            'users as regular_users_count' => function ($query) {
                $query->where('is_admin', 0); // Only count non-admin users
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
            'description' => 'nullable|string|max:500',
        ]);

        Department::create($validated);

        return redirect()
            ->route('admin.departments.index')
            ->with('success', 'Department added successfully!');
    }

    public function update(Request $request, $id)
    {
        $department = Department::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:departments,name,' . $id,
            'description' => 'nullable|string|max:500',
        ]);

        $department->update($validated);

        return redirect()
            ->route('admin.departments.index')
            ->with('success', 'Department updated successfully!');
    }

    public function destroy($id)
    {
        $department = Department::findOrFail($id);
        
        // Check if department has users
        if ($department->users()->count() > 0) {
            return redirect()
                ->route('admin.departments.index')
                ->with('error', 'Cannot delete department with existing users. Please reassign users first.');
        }

        $department->delete();

        return redirect()
            ->route('admin.departments.index')
            ->with('success', 'Department deleted successfully!');
    }
}