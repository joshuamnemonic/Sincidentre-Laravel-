<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserManagementController extends Controller
{
    /**
     * Display a listing of all registered users.
     */
    public function index()
    {
        // Fetch all users ordered by registration date
        $users = User::orderBy('created_at', 'desc')->get();

        // Return the admin.users Blade view with the users
        return view('admin.users', compact('users'));
    }

    /**
     * Show a single user details (optional).
     */
    public function show($id)
    {
        $user = User::findOrFail($id);

        return view('admin.usershow', compact('user'));
    }

    /**
     * Suspend a user (optional).
     */
    public function suspend($id)
    {
        $user = User::findOrFail($id);
        $user->status = 'Suspended';
        $user->save();

        return redirect()->route('admin.users')->with('success', 'User suspended successfully.');
    }

    /**
     * Delete a user (optional).
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('admin.users')->with('success', 'User deleted successfully.');
    }
}
