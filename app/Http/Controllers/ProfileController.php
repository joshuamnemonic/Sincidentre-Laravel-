<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    // Show profile page
    public function show()
    {
        return view('user.profile');
    }

    // Update profile
    public function update(Request $request)
    {
        $user = Auth::user();

        // ✅ Validate inputs
        $request->validate([
            'first_name'            => 'required|string|max:255',
            'last_name'             => 'required|string|max:255',
            'email'                 => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone'                 => 'nullable|string|max:20',
            'profile_picture'       => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'current_password'      => 'nullable|string',
            'new_password'          => 'nullable|string|min:6|confirmed',
        ]);

        // ✅ Update basic info
        $user->first_name = $request->first_name;
        $user->last_name  = $request->last_name;
        $user->email      = $request->email;
        $user->phone      = $request->phone;

        // ✅ Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            $file     = $request->file('profile_picture');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/profile_pictures'), $filename);

            $user->profile_picture = 'uploads/profile_pictures/' . $filename;
        }

        // ✅ Handle password change (only if provided)
        if ($request->filled('current_password') && $request->filled('new_password')) {
            if (Hash::check($request->current_password, $user->password)) {
                $user->password = Hash::make($request->new_password);
            } else {
                return back()->withErrors(['current_password' => 'Current password is incorrect']);
            }
        }

        // ✅ Save changes
        $user->save();

        return redirect()->route('profile')->with('success', 'Profile updated successfully!');
    }
}
