<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Department;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    private function departmentShortName(?Department $department): string
    {
        $name = trim((string) ($department->name ?? ''));
        if ($name === '') {
            return 'Department';
        }

        $normalized = strtolower($name);
        $knownShortNames = [
            'college of technology' => 'CoT',
            'college of education' => 'CoED',
            'college of hospitality and tourism management' => 'CoHTM',
        ];

        if (isset($knownShortNames[$normalized])) {
            return $knownShortNames[$normalized];
        }

        preg_match_all('/[A-Za-z]+/', $name, $matches);
        $words = $matches[0] ?? [];
        if (empty($words)) {
            return $name;
        }

        $acronym = '';
        foreach ($words as $word) {
            $firstChar = strtoupper(substr($word, 0, 1));
            if ($firstChar !== '') {
                $acronym .= $firstChar;
            }
        }

        return $acronym !== '' ? $acronym : $name;
    }

    private function baseManageableUsersQuery(User $admin)
    {
        $query = User::query()
            ->where('is_department_student_discipline_officer', 0)
            ->where('is_top_management', 0);

        if (!(bool) $admin->is_top_management) {
            $query->where('department_id', $admin->department_id);
        }

        return $query;
    }

    private function findManageableUserOrFail(int $id, User $admin): User
    {
        return $this->baseManageableUsersQuery($admin)->findOrFail($id);
    }

    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        $admin = Auth::user();
        $query = $this->baseManageableUsersQuery($admin)
            ->with(['department'])
            ->withCount('reports')
            ->latest();

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

        // Department filter remains available for Top Management only.
        if ((bool) $admin->is_top_management && $request->filled('department')) {
            $query->where('department_id', $request->department);
        }

        $users = $query->paginate(15);

        // Statistics
        $statsQuery = $this->baseManageableUsersQuery($admin);
        $totalUsers = (clone $statsQuery)->count();
        $activeUsers = (clone $statsQuery)->where('status', 'active')->count();
        $suspendedUsers = (clone $statsQuery)->where('status', 'suspended')->count();
        $deactivatedUsers = (clone $statsQuery)->where('status', 'deactivated')->count();

        $currentDepartment = $admin->department;
        $departmentShortName = $this->departmentShortName($currentDepartment);
        $totalUsersTitle = (bool) $admin->is_top_management
            ? 'Total Users'
            : 'TOTAL USERS IN ' . $departmentShortName;

        // Get all departments for filter
        $departments = Department::orderBy('name')->get();
        $canFilterDepartment = (bool) $admin->is_top_management;

        return view('admin.users', compact(
            'users', 
            'totalUsers', 
            'activeUsers', 
            'suspendedUsers', 
            'deactivatedUsers',
            'departments',
            'canFilterDepartment',
            'totalUsersTitle'
        ));
    }

    /**
     * Display a specific user
     */
    public function show($id)
    {
        $admin = Auth::user();
        $user = $this->findManageableUserOrFail((int) $id, $admin)->load(['department', 'reports.category', 'suspendedBy']);

        return view('admin.usershow', compact('user'));
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $admin = Auth::user();
        $user = $this->findManageableUserOrFail((int) $id, $admin);
        
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
        $admin = Auth::user();
        $user = $this->findManageableUserOrFail((int) $id, $admin);

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
            'department_id' => ((bool) $admin->is_top_management ? 'required' : 'nullable') . '|exists:departments,id',
            'phone' => 'nullable|string|max:20',
        ]);

        if (!(bool) $admin->is_top_management) {
            $validated['department_id'] = $admin->department_id;
        }

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
        $admin = Auth::user();
        $user = $this->findManageableUserOrFail((int) $id, $admin);

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
            'suspended_until' => 'required|date|after:now',
        ]);

        $user->update([
            'status' => 'suspended',
            'suspension_reason' => $request->reason,
            'suspended_at' => now(),
            'suspended_until' => $request->date('suspended_until'),
            'suspended_by' => Auth::id(),
            'deactivation_category' => null,
            'deactivated_at' => null,
        ]);

        // Log the action
        Activity::create([
            'user_id' => $user->id,
            'action' => 'User Suspended',
            'performed_by' => Auth::id(),
            'remarks' => 'Reason: ' . $request->reason . ' | Until: ' . optional($request->date('suspended_until'))->format('M d, Y h:i A'),
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
        $admin = Auth::user();
        $user = $this->findManageableUserOrFail((int) $id, $admin);

        $oldStatus = $user->status;

        $user->update([
            'status' => 'active',
            'suspension_reason' => null,
            'suspended_at' => null,
            'suspended_until' => null,
            'suspended_by' => null,
            'deactivation_category' => null,
            'deactivated_at' => null,
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
        $admin = Auth::user();
        $user = $this->findManageableUserOrFail((int) $id, $admin);

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

        $request->validate([
            'deactivation_category' => [
                'required',
                Rule::in(['graduated', 'left_institution', 'duplicate_account', 'policy_violation', 'other']),
            ],
            'reason' => 'nullable|string|max:500',
        ]);

        $deactivationCategoryLabels = [
            'graduated' => 'Graduated',
            'left_institution' => 'Left Institution',
            'duplicate_account' => 'Duplicate Account',
            'policy_violation' => 'Policy Violation',
            'other' => 'Other',
        ];

        $categoryCode = (string) $request->input('deactivation_category');
        $categoryLabel = $deactivationCategoryLabels[$categoryCode] ?? 'Other';
        $reason = trim((string) $request->input('reason', ''));
        $finalReason = $reason !== ''
            ? $reason
            : ('Account deactivated by Department Student Discipline Officer. Category: ' . $categoryLabel);

        $user->update([
            'status' => 'deactivated',
            'suspension_reason' => $finalReason,
            'suspended_at' => now(),
            'suspended_until' => null,
            'suspended_by' => Auth::id(),
            'deactivation_category' => $categoryCode,
            'deactivated_at' => now(),
        ]);

        // Log the action
        Activity::create([
            'user_id' => $user->id,
            'action' => 'User Deactivated',
            'performed_by' => Auth::id(),
            'remarks' => 'Category: ' . $categoryLabel . ' | Reason: ' . $finalReason,
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
        if (!request()->filled('deactivation_category')) {
            request()->merge(['deactivation_category' => 'other']);
        }

        return $this->deactivate(request(), $id);
    }
}


