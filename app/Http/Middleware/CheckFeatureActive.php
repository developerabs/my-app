<?php

namespace App\Http\Middleware;

use App\Services\FeatureService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckFeatureActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $featureKey): Response
    {
        if (!FeatureService::getActive($featureKey)) {
            return redirect()->route('dashboard')->with('error', 'This feature is not included in your plan.');
        }
        return $next($request);
    }
}
