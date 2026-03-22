<?php

// app/Http/Controllers/AuthController.php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use App\Models\PendingEmployeeRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    private const OTP_MAX_ATTEMPTS = 5;
    private const OTP_LOCKOUT_MINUTES = 15;
    private const EMPLOYEE_STAFF_ACCESS_CODE = 'LLCC@2026*&^%$#@!';

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

        if ($isEmployeeStaff) {
            return $this->handleEmployeeRegistration($request);
        }

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|regex:/@llcc\.edu\.ph$/|unique:users,email',
            'password'   => 'required|min:6|confirmed',
            'registrant_type' => 'required|in:student,faculty',
            'department_id' => 'required|exists:departments,id',
        ]);

        $otp = (string) random_int(100000, 999999);
        $otpExpiry = now()->addMinutes(10);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'email'      => (string) $request->email,
            'password'   => Hash::make($request->password),
            'department_id' => $request->department_id,
            'registrant_type' => $request->registrant_type,
            'employee_office' => null,
            'employee_id_number' => null,
            'email_verification_otp' => $otp,
            'email_verification_otp_expires_at' => $otpExpiry,
            'otp_attempts' => 0,
            'otp_locked_until' => null,
            'email_verified_at' => null,
            'status' => 'active'
        ]);

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

    private function handleEmployeeRegistration(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'employee_username' => 'required|string|max:50|unique:users,username|unique:pending_employee_registrations,username',
            'employee_email' => 'required|email|unique:users,email|unique:pending_employee_registrations,email',
            'password'   => 'required|min:6|confirmed',
        ]);

        PendingEmployeeRegistration::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'username' => $request->employee_username,
            'email' => $request->employee_email,
            'password' => Hash::make($request->password),
            'status' => PendingEmployeeRegistration::STATUS_PENDING,
        ]);

        // TODO: Send notification to Top Management about pending registration

        return redirect()->route('sinclogin')->with('success',
            'Employee/Staff registration request submitted successfully. You will receive an email notification once your registration is approved by Top Management.');
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
        $loginType = $request->input('login_type', 'student_faculty');

        // Validate based on login type
        if ($loginType === 'employee') {
            $request->validate([
                'username' => 'required|string',
                'password' => 'required'
            ]);

            $credentials = [
                'username' => $request->username,
                'password' => $request->password,
            ];
        } else {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required'
            ]);

            $credentials = [
                'email' => $request->email,
                'password' => $request->password,
            ];
        }

        // Attempt to authenticate the user
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
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

                $suspensionReason = trim((string) ($user->suspension_reason ?? ''));
                if ($suspensionReason !== '') {
                    $suspensionMessage .= ' Reason: ' . $suspensionReason;
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

                $deactivationMessage = 'Your account has been deactivated. Please contact the Department Student Discipline Officer to reactivate your account.';
                $deactivationCategory = trim((string) ($user->deactivation_category ?? ''));
                $deactivationReason = trim((string) ($user->suspension_reason ?? ''));

                if ($deactivationCategory !== '') {
                    $deactivationMessage .= ' Category: ' . str_replace('_', ' ', ucfirst($deactivationCategory)) . '.';
                }

                if ($deactivationReason !== '') {
                    $deactivationMessage .= ' Reason: ' . $deactivationReason;
                }
                
                return back()->withErrors([
                    'email' => $deactivationMessage
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


