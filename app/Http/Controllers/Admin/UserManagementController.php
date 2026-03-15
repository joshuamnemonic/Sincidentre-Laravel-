<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Department;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserManagementController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        $query = User::with(['department'])
            ->withCount('reports')
            ->where('is_department_student_discipline_officer', 0)
            ->where('is_top_management', 0); // Only show regular users

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Department filter
        if ($request->filled('department')) {
            $query->where('department_id', $request->department);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15);

        // Statistics
        $totalUsers = User::where('is_department_student_discipline_officer', 0)->where('is_top_management', 0)->count();
        $activeUsers = User::where('is_department_student_discipline_officer', 0)->where('is_top_management', 0)->where('status', 'active')->count();
        $suspendedUsers = User::where('is_department_student_discipline_officer', 0)->where('is_top_management', 0)->where('status', 'suspended')->count();
        $deactivatedUsers = User::where('is_department_student_discipline_officer', 0)->where('is_top_management', 0)->where('status', 'deactivated')->count();

        // Get all departments for filter
        $departments = Department::orderBy('name')->get();

        return view('admin.users', compact(
            'users', 
            'totalUsers', 
            'activeUsers', 
            'suspendedUsers', 
            'deactivatedUsers',
            'departments'
        ));
    }

    /**
     * Display a specific user
     */
    public function show($id)
    {
        $user = User::with(['department', 'reports.category', 'suspendedBy'])
            ->findOrFail($id);

        return view('admin.usershow', compact('user'));
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $user = User::findOrFail($id);
        
        // Prevent editing department student discipline officer accounts
        if ($user->is_department_student_discipline_officer || $user->is_top_management) {
            return redirect()
                ->route('admin.users')
            ->with('error', 'Cannot edit privileged management accounts.');
        }

        $departments = Department::orderBy('name')->get();

        return view('admin.edit', compact('user', 'departments'));
    }

    /**
     * Update user information
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Prevent editing department student discipline officer accounts
        if ($user->is_department_student_discipline_officer || $user->is_top_management) {
            return redirect()
                ->route('admin.users')
            ->with('error', 'Cannot edit privileged management accounts.');
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'department_id' => 'required|exists:departments,id',
            'phone' => 'nullable|string|max:20',
        ]);

        $user->update($validated);

        // Log the action
        Activity::create([
            'user_id' => $user->id,
            'action' => 'User Information Updated',
            'performed_by' => Auth::id(),
            'remarks' => 'Department Student Discipline Officer updated user information',
        ]);

        return redirect()
            ->route('admin.users.show', $user->id)
            ->with('success', 'User information updated successfully!');
    }

    /**
     * Suspend a user
     */
    public function suspend(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Prevent suspending department student discipline officer accounts
        if ($user->is_department_student_discipline_officer || $user->is_top_management) {
            return redirect()
                ->back()
            ->with('error', 'Cannot suspend privileged management accounts.');
        }

        // Prevent suspending yourself
        if ($user->id === Auth::id()) {
            return redirect()
                ->back()
                ->with('error', 'You cannot suspend your own account.');
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $user->update([
            'status' => 'suspended',
            'suspension_reason' => $request->reason,
            'suspended_at' => now(),
            'suspended_by' => Auth::id(),
        ]);

        // Log the action
        Activity::create([
            'user_id' => $user->id,
            'action' => 'User Suspended',
            'performed_by' => Auth::id(),
            'remarks' => 'Reason: ' . $request->reason,
        ]);

        return redirect()
            ->back()
            ->with('success', 'User suspended successfully.');
    }

    /**
     * Activate a user (reactivate from suspended or deactivated)
     */
    public function activate($id)
    {
        $user = User::findOrFail($id);

        $oldStatus = $user->status;

        $user->update([
            'status' => 'active',
            'suspension_reason' => null,
            'suspended_at' => null,
            'suspended_by' => null,
        ]);

        // Log the action
        Activity::create([
            'user_id' => $user->id,
            'action' => 'User Reactivated',
            'performed_by' => Auth::id(),
            'old_status' => $oldStatus,
            'new_status' => 'active',
            'remarks' => "User account reactivated from {$oldStatus} status",
        ]);

        return redirect()
            ->back()
            ->with('success', 'User account reactivated successfully.');
    }

    /**
     * Deactivate a user (soft alternative to delete)
     */
    public function deactivate(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Prevent deactivating department student discipline officer accounts
        if ($user->is_department_student_discipline_officer || $user->is_top_management) {
            return redirect()
                ->back()
            ->with('error', 'Cannot deactivate privileged management accounts.');
        }

        // Prevent deactivating yourself
        if ($user->id === Auth::id()) {
            return redirect()
                ->back()
                ->with('error', 'You cannot deactivate your own account.');
        }

        $user->update([
            'status' => 'deactivated',
            'suspension_reason' => $request->reason ?? 'Account deactivated by Department Student Discipline Officer',
            'suspended_at' => now(),
            'suspended_by' => Auth::id(),
        ]);

        // Log the action
        Activity::create([
            'user_id' => $user->id,
            'action' => 'User Deactivated',
            'performed_by' => Auth::id(),
            'remarks' => $request->reason ?? 'Account deactivated by Department Student Discipline Officer',
        ]);

        return redirect()
            ->back()
            ->with('success', 'User account deactivated successfully.');
    }

    /**
     * Delete user (kept for backward compatibility, but recommend using deactivate instead)
     */
    public function destroy($id)
    {
        // Redirect to deactivate instead
        return $this->deactivate(request(), $id);
    }
}


