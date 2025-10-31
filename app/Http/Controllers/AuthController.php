<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Show login form
    public function showLogin()
    {
        return view('auth.sinclogin'); 
        // Make sure this exists: resources/views/auth/sinclogin.blade.php
    }

    // Handle login form submit
    public function login(Request $request)
{
    $credentials = $request->only('email', 'password');

    if (Auth::attempt($credentials)) {
        $user = Auth::user();

        // ✅ Check if user is admin (is_admin = 1 in DB)
        if ($user->is_admin == 1) {
            return redirect()->route('admin.admindashboard');
        }

        // ✅ Normal user
        return redirect()->route('dashboard');
    }

    // ❌ Invalid credentials
    return back()->withErrors([
        'email' => 'Invalid login credentials.',
    ]);
}




    // Show register form
    public function showRegister()
    {
        return view('auth.register'); 
        // Make sure this exists: resources/views/auth/register.blade.php
    }

    // Handle register form submit
public function register(Request $request)
{
    $request->validate([
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:6|confirmed',
    ]);

    User::create([
    'first_name' => $request->first_name,
    'last_name' => $request->last_name,
    'email' => $request->email,
    'password' => Hash::make($request->password),
    'is_admin' => 0, // 👈 default normal user
]);


    return redirect()->route('sinclogin')->with('success', 'Account created! Please log in.');
}


public function logout(Request $request)
{
    Auth::logout();

    // Invalidate the session
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    // Redirect to login page
    return redirect()->route('sinclogin');
}

}
