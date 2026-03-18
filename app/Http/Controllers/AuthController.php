<?php

// app/Http/Controllers/AuthController.php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private const OTP_MAX_ATTEMPTS = 5;
    private const OTP_LOCKOUT_MINUTES = 15;
    private const EMPLOYEE_STAFF_ACCESS_CODE = 'LLCC@2026*&^%$#@!';

    private function generateEmployeePlaceholderEmail(string $firstName, string $lastName): string
    {
        $first = Str::slug($firstName, '');
        $last = Str::slug($lastName, '');
        $namePart = trim($first . $last);
        $namePart = $namePart !== '' ? $namePart : 'employee';

        do {
            $candidate = $namePart . '+' . now()->format('YmdHis') . random_int(100, 999) . '@employee.llcc.local';
        } while (User::where('email', $candidate)->exists());

        return $candidate;
    }

    public function showLoginRegister()
    {
        $departments = Department::all();
        return view('auth.sincidentre', compact('departments'));
    }

    public function showRegister()
    {
        $departments = Department::orderBy('name')->get();
        return view('auth.register', compact('departments'));
    }

    public function showLogin()
    {
        $departments = Department::orderBy('name')->get();
        return view('auth.sinclogin', compact('departments'));
    }

    public function register(Request $request)
    {
        $isEmployeeStaff = $request->input('registrant_type') === 'employee_staff';

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'exclude_if:registrant_type,employee_staff|required|email|regex:/@llcc\.edu\.ph$/|unique:users,email',
            'password'   => 'required|min:6|confirmed',
            'registrant_type' => 'required|in:student,faculty,employee_staff',
            'department_id' => 'required_unless:registrant_type,employee_staff|nullable|exists:departments,id',
            'employee_access_code' => 'required_if:registrant_type,employee_staff|nullable|string|max:100',
        ]);

        if ($isEmployeeStaff && !hash_equals(self::EMPLOYEE_STAFF_ACCESS_CODE, (string) $request->employee_access_code)) {
            return back()->withInput()->withErrors([
                'employee_access_code' => 'The code provided by Top Management is incorrect.',
            ]);
        }

        $otp = (string) random_int(100000, 999999);
        $otpExpiry = now()->addMinutes(10);
        $resolvedEmail = $isEmployeeStaff
            ? $this->generateEmployeePlaceholderEmail((string) $request->first_name, (string) $request->last_name)
            : (string) $request->email;

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'email'      => $resolvedEmail,
            'password'   => Hash::make($request->password),
            'department_id' => $isEmployeeStaff ? null : $request->department_id,
            'registrant_type' => $request->registrant_type,
            'employee_office' => null,
            'employee_id_number' => $isEmployeeStaff ? (string) $request->employee_access_code : null,
            'email_verification_otp' => $isEmployeeStaff ? null : $otp,
            'email_verification_otp_expires_at' => $isEmployeeStaff ? null : $otpExpiry,
            'otp_attempts' => 0,
            'otp_locked_until' => null,
            'email_verified_at' => $isEmployeeStaff ? now() : null,
            'status' => 'active'
        ]);

        if ($isEmployeeStaff) {
            return redirect()->route('sinclogin')->with('success', 'Employee/Staff registration completed and automatically verified. You can now sign in.');
        }

        Mail::raw(
            "Your Sincidentre registration OTP is {$otp}. It will expire in 10 minutes.",
            function ($message) use ($user) {
                $message->to($user->email)
                    ->from(config('mail.from.address'), 'Sincidentre')
                    ->subject('Sincidentre Registration OTP Verification');
            }
        );

        $request->session()->put('registration_otp_user_id', $user->id);

        return redirect()->route('sincregister.otp.form')
            ->with('success', 'Registration received. Enter the OTP sent to your email to complete verification.');
    }

    public function showOtpForm(Request $request)
    {
        $userId = $request->session()->get('registration_otp_user_id');

        if (!$userId) {
            return redirect()->route('sinclogin')->withErrors([
                'email' => 'No pending registration verification found.',
            ]);
        }

        $user = User::find($userId);

        if (!$user) {
            $request->session()->forget('registration_otp_user_id');

            return redirect()->route('sinclogin')->withErrors([
                'email' => 'Your pending registration could not be found. Please register again.',
            ]);
        }

        return view('auth.verify-otp', ['email' => $user->email]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $userId = $request->session()->get('registration_otp_user_id');
        $user = $userId ? User::find($userId) : null;

        if (!$user) {
            return redirect()->route('sincregister')->withErrors([
                'email' => 'Your OTP session has expired. Please register again.',
            ]);
        }

        if ($user->otp_locked_until && now()->lt($user->otp_locked_until)) {
            $remainingMinutes = (int) ceil(now()->diffInSeconds($user->otp_locked_until) / 60);

            return back()->withErrors([
                'otp' => "Too many incorrect attempts. Try again in {$remainingMinutes} minute(s).",
            ]);
        }

        if (!$user->email_verification_otp || !$user->email_verification_otp_expires_at || now()->greaterThan($user->email_verification_otp_expires_at)) {
            return back()->withErrors([
                'otp' => 'OTP has expired. Please request a new code.',
            ]);
        }

        if (!hash_equals($user->email_verification_otp, $request->otp)) {
            $attempts = ((int) $user->otp_attempts) + 1;

            $user->forceFill([
                'otp_attempts' => $attempts,
                'otp_locked_until' => $attempts >= self::OTP_MAX_ATTEMPTS ? now()->addMinutes(self::OTP_LOCKOUT_MINUTES) : null,
            ])->save();

            if ($attempts >= self::OTP_MAX_ATTEMPTS) {
                return back()->withErrors([
                    'otp' => 'Too many incorrect attempts. OTP verification is locked for 15 minutes.',
                ]);
            }

            $remainingAttempts = self::OTP_MAX_ATTEMPTS - $attempts;

            return back()->withErrors([
                'otp' => "Invalid OTP. {$remainingAttempts} attempt(s) remaining before lockout.",
            ]);
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'email_verification_otp' => null,
            'email_verification_otp_expires_at' => null,
            'otp_attempts' => 0,
            'otp_locked_until' => null,
        ])->save();

        $request->session()->forget('registration_otp_user_id');

        return redirect()->route('sinclogin')->with('success', 'Email verified successfully. You can now log in.');
    }

    public function resendOtp(Request $request)
    {
        $userId = $request->session()->get('registration_otp_user_id');
        $user = $userId ? User::find($userId) : null;

        if (!$user) {
            return redirect()->route('sincregister')->withErrors([
                'email' => 'Your OTP session has expired. Please register again.',
            ]);
        }

        if ($user->otp_locked_until && now()->lt($user->otp_locked_until)) {
            $remainingMinutes = (int) ceil(now()->diffInSeconds($user->otp_locked_until) / 60);

            return back()->withErrors([
                'otp' => "Resend is temporarily blocked due to failed attempts. Try again in {$remainingMinutes} minute(s).",
            ]);
        }

        $otp = (string) random_int(100000, 999999);

        $user->forceFill([
            'email_verification_otp' => $otp,
            'email_verification_otp_expires_at' => now()->addMinutes(10),
            'otp_attempts' => 0,
            'otp_locked_until' => null,
        ])->save();

        Mail::raw(
            "Your Sincidentre registration OTP is {$otp}. It will expire in 10 minutes.",
            function ($message) use ($user) {
                $message->to($user->email)
                    ->from(config('mail.from.address'), 'Sincidentre')
                    ->subject('Sincidentre Registration OTP Verification');
            }
        );

        return back()->with('success', 'A new OTP has been sent to your email.');
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

            if ($user->status === 'suspended' && $user->suspended_until && now()->greaterThanOrEqualTo($user->suspended_until)) {
                $user->update([
                    'status' => 'active',
                    'suspension_reason' => null,
                    'suspended_at' => null,
                    'suspended_until' => null,
                    'suspended_by' => null,
                    'deactivation_category' => null,
                    'deactivated_at' => null,
                ]);

                $user->refresh();
            }

            // ✅ CHECK 1: Check if account is suspended
            if ($user->status === 'suspended') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $suspensionMessage = 'Your account has been suspended. Please contact the Department Student Discipline Officer for more information.';
                if ($user->suspended_until) {
                    $suspensionMessage = 'Your account is suspended until ' . $user->suspended_until->format('M d, Y h:i A') . '. Please contact the Department Student Discipline Officer if you need assistance.';
                }
                
                return back()->withErrors([
                    'email' => $suspensionMessage
                ])->withInput($request->only('email'));
            }

            // ✅ CHECK 2: Check if account is deactivated
            if ($user->status === 'deactivated') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                return back()->withErrors([
                    'email' => 'Your account has been deactivated. Please contact the Department Student Discipline Officer to reactivate your account.'
                ])->withInput($request->only('email'));
            }

            // ✅ CHECK 3: Check if email is verified
            if (!$user->hasVerifiedEmail()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                return back()->withErrors([
                    'email' => 'Please complete email verification first. Use the OTP sent during registration.'
                ])->withInput($request->only('email'));
            }

            // ✅ All checks passed - redirect based on user role
            if ($user->is_department_student_discipline_officer == 1 || $user->is_top_management == 1) {
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


