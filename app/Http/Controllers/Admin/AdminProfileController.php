<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
            'profile_picture'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
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
            $this->deleteProfilePicture($user->profile_picture);
            $path = $request->file('profile_picture')->storePublicly('profile_pictures', 'public');
            $user->profile_picture = $path;
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

    private function deleteProfilePicture(?string $path): void
    {
        $path = trim((string) $path);
        if ($path === '') {
            return;
        }

        if (Str::startsWith($path, 'uploads/')) {
            $legacyPath = public_path($path);
            if (file_exists($legacyPath)) {
                @unlink($legacyPath);
            }
            return;
        }

        if (Str::startsWith($path, 'storage/')) {
            $path = Str::after($path, 'storage/');
        }

        Storage::disk('public')->delete($path);
    }
}
