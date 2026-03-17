<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureHandlingResponseCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        $pending = session('pending_handling_response');

        if (!is_array($pending)) {
            return $next($request);
        }

        $user = Auth::user();
        if (!$user) {
            return $next($request);
        }

        $pendingUserId = (int) ($pending['user_id'] ?? 0);
        $pendingReportId = (int) ($pending['report_id'] ?? 0);

        if ($pendingUserId !== (int) $user->id || $pendingReportId <= 0) {
            session()->forget('pending_handling_response');
            return $next($request);
        }

        $route = $request->route();
        $routeName = $route?->getName();

        if (!$routeName) {
            return $next($request);
        }

        $allowedRouteNames = [
            'admin.handlereports.show',
            'admin.handlereports.update',
        ];

        if (in_array($routeName, $allowedRouteNames, true)) {
            $routeReportId = (int) ($route->parameter('id') ?? 0);
            if ($routeReportId === $pendingReportId) {
                return $next($request);
            }
        }

        return redirect()
            ->route('admin.handlereports.show', $pendingReportId)
            ->with('error', 'Complete the Add Handling Response form before navigating away from this report.');
    }
}
