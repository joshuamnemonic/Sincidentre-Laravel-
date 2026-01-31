<?php

// app/Http/Controllers/AuthController.php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;

class AuthController extends Controller
{
    public function showLoginRegister()
    {
        $departments = Department::all();
        return view('auth.sincidentre', compact('departments'));
    }

    public function showRegister()
    {
        $departments = Department::orderBy('name')->get();
        return view('auth.sincregister', compact('departments'));
    }

    public function showLogin()
    {
        $departments = Department::orderBy('name')->get();
        return view('auth.sinclogin', compact('departments'));
    }

    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|regex:/@llcc\.edu\.ph$/|unique:users,email',
            'password'   => 'required|min:6|confirmed',
            'department_id' => 'required|exists:departments,id'
        ]);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'email'      => $request->email,
            'password'   => Hash::make($request->password),
            'department_id' => $request->department_id,
            'status' => 'active' // ✅ Set default status
        ]);

        // Fire the Registered event which triggers email verification
        event(new Registered($user));

        return redirect()->route('sinclogin')->with('success', 'Registration complete! Please check your email to verify your account.');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // Attempt to authenticate the user
        if (Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            // ✅ CHECK 1: Check if account is suspended
            if ($user->status === 'suspended') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                return back()->withErrors([
                    'email' => 'Your account has been suspended. Please contact an administrator for more information.'
                ])->withInput($request->only('email'));
            }

            // ✅ CHECK 2: Check if account is deactivated
            if ($user->status === 'deactivated') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                return back()->withErrors([
                    'email' => 'Your account has been deactivated. Please contact an administrator to reactivate your account.'
                ])->withInput($request->only('email'));
            }

            // ✅ CHECK 3: Check if email is verified
            if (!$user->hasVerifiedEmail()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                return back()->withErrors([
                    'email' => 'Please verify your email address first. Check your inbox for the verification link.'
                ])->withInput($request->only('email'));
            }

            // ✅ All checks passed - redirect based on user role
            if ($user->is_admin == 1) {
                return redirect()->intended(route('admin.admindashboard'));
            }
            
            // Regular user dashboard
            return redirect()->intended(route('dashboard'));
        }

        // Authentication failed
        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->withInput($request->only('email'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('sinclogin')->with('success', 'You have been logged out successfully.');
    }
}