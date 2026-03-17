<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IsTopManagement
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && (bool) (Auth::user()->is_top_management ?? false)) {
            return $next($request);
        }

        abort(403, 'Unauthorized action.');
    }
}
