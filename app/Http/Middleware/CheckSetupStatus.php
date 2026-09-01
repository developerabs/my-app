<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSetupStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        $setup = Setting::where('group', 'setup')
                    ->pluck('value', 'key')
                    ->toArray();

        $isSetupComplete = filter_var(
            $setup['is_setup_complete'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        $currentStep = $setup['setup_step'] ?? 'initial';

        // যদি Setup Complete হয়
        if ($isSetupComplete) {
            // setup page এ গেলে dashboard এ পাঠাবে
            if ($request->routeIs('setup.*')) {
                return redirect()->route('dashboard');
            }

            return $next($request);
        }

        // POST Request Allow
        if ($request->isMethod('post')) {
            return $next($request);
        }

        $expectedRoute = $this->getRouteByStep($currentStep);

        // User already correct setup page এ আছে
        if ($request->routeIs($expectedRoute)) {
            return $next($request);
        }

        // অন্য যেকোনো page block
        return redirect()->route($expectedRoute);
    }

    private function getRouteByStep(string $step): string
    {
        return match ($step) {
            'initial'      => 'setup.index',
            'regional'     => 'setup.regional',
            'branch'       => 'setup.branch',       // ১. ব্রাঞ্চ আগে আসবে
            'accounting'   => 'setup.accounting',   // ২. একাউন্টিং পরে আসবে
            'complete'     => 'setup.complete',
            default        => 'setup.index',
        };
    }
}