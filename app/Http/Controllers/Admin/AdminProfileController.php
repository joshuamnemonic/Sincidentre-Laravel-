<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminProfileController extends Controller
{
    /**
     * Show admin profile page
     */
    public function show()
    {
        return view('admin.profile');
    }

    /**
     * Update admin profile
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        // Validate inputs
        $request->validate([
            'email'                 => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone'                 => 'nullable|string|max:20',
            'profile_picture'       => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'current_password'      => 'nullable|string',
            'new_password'          => 'nullable|string|min:8|confirmed',
        ]);

        $normalizedEmail = strtolower(trim((string) $request->email));
        if ($normalizedEmail !== '' && $normalizedEmail !== strtolower((string) $user->email)) {
            $user->email = $normalizedEmail;
            $user->email_verified_at = now();
        }

        // Update phone number
        if ($request->filled('phone')) {
            $user->phone = $request->phone;
        }

        // Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            $file     = $request->file('profile_picture');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/profile_pictures'), $filename);

            // Delete old profile picture if exists
            if ($user->profile_picture && file_exists(public_path($user->profile_picture))) {
                @unlink(public_path($user->profile_picture));
            }

            $user->profile_picture = 'uploads/profile_pictures/' . $filename;
        }

        // Handle password change (only if both current and new password provided)
        if ($request->filled('current_password') && $request->filled('new_password')) {
            if (Hash::check($request->current_password, $user->password)) {
                $user->password = Hash::make($request->new_password);
            } else {
                return back()->withErrors(['current_password' => 'Current password is incorrect']);
            }
        }

        // Save changes
        $user->save();

        return redirect()->route('admin.profile')->with('success', 'Profile updated successfully!');
    }
}
