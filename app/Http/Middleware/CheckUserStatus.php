<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckUserStatus
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Check if user is not active
            if ($user->status !== 'active') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                $message = $user->status === 'suspended' 
                    ? 'Your account has been suspended.' 
                    : 'Your account has been deactivated.';
                
                return redirect()
                    ->route('sinclogin')
                    ->withErrors(['email' => $message . ' Please contact an administrator.']);
            }
        }

        return $next($request);
    }
}