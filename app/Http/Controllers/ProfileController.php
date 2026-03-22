<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    // Show profile page
    public function show()
    {
        return view('user.Profile');
    }

    // Update profile
    public function update(Request $request)
    {
        $user = Auth::user();
        $formType = (string) $request->input('form_type', 'profile');

        if ($formType === 'password') {
            $validated = $request->validate([
                'current_password' => ['required', 'current_password'],
                'new_password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            ]);

            $user->password = Hash::make($validated['new_password']);
            $user->save();

            return redirect()->route('profile')->with('success', 'Password updated successfully.');
        }

        $validated = $request->validate([
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9\-\+\s\(\)]+$/'],
            'profile_picture' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_profile_picture' => ['nullable', 'boolean'],
        ]);

        $normalizedPhone = isset($validated['phone'])
            ? preg_replace('/[^0-9\+]/', '', (string) $validated['phone'])
            : null;

        $user->phone = $normalizedPhone !== '' ? $normalizedPhone : null;

        if (!empty($validated['remove_profile_picture']) && $user->profile_picture) {
            $this->deleteProfilePicture($user->profile_picture);
            $user->profile_picture = null;
        }

        if ($request->hasFile('profile_picture')) {
            $this->deleteProfilePicture($user->profile_picture);
            $path = $request->file('profile_picture')->storePublicly('profile_pictures', 'public');
            $user->profile_picture = $path;
        }

        $user->save();

        return redirect()->route('profile')->with('success', 'Profile details updated successfully.');
    }

    private function deleteProfilePicture(?string $path): void
    {
        $path = trim((string) $path);
        if ($path === '') {
            return;
        }

        if (Str::startsWith($path, 'uploads/')) {
            $legacyPath = public_path($path);
            if (File::exists($legacyPath)) {
                File::delete($legacyPath);
            }
            return;
        }

        if (Str::startsWith($path, 'storage/')) {
            $path = Str::after($path, 'storage/');
        }

        Storage::disk('public')->delete($path);
    }
}
