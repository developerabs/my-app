<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantSubscriptionCheckMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();
        if (!$tenant) return $next($request);

        // Billing routes skip
        if ($request->routeIs('billing.*')) {
            return $next($request);
        }

        // ১. Billing Mode Check
        if ($tenant->status === 'billing') {
            $message = 'Your subscription requires billing. Please update your payment information.';

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => $message, 'redirect' => route('billing', $tenant->id)], 402); // 402: Payment Required
            }

            return redirect()->route('billing', $tenant->id)->with('error', $message);
        }

        // ২. View-only mode for expired tenants
        if ($tenant->status === 'expired') {
            if (in_array($request->method(), ['POST', 'PUT', 'DELETE', 'PATCH'])) {
                $message = 'Your subscription has expired. Please contact the administrator.';

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['error' => $message, 'redirect' => route('billing', $tenant->id)], 403); // 403: Forbidden
                }

                return redirect()->route('billing', $tenant->id)->with('error', $message);
            }
        }

        return $next($request);
    }
}
