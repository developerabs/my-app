<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\FeatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckFeatureLimit
{
    public function handle(Request $request, Closure $next, string $limitKey, string $modelName): Response
    {
        // 1. Fetch the limit from FeatureService
        $limit = FeatureService::getLimit($limitKey);

        // 2. Unlimited Check
        if (is_null($limit) || $limit === -1 || $limit === 'unlimited') {
            return $next($request);
        }

        // 3. Build the full model path
        $modelClass = '\App\Models\\' . $modelName;

        if (!class_exists($modelClass)) {
            Log::warning("Feature Limit Middleware: Model {$modelClass} not found.");
            return $next($request);
        }

        // 4. Optimized Count Tracking
        $cacheKey = "limit_count_" . strtolower($modelName) . "_" . tenant('id');

        // English Comment: Retrieve from atomic cache layer or calculate fallback using exact DB counts if state decays
        $currentCount = Cache::tags([tenant_tag()])->rememberForever($cacheKey, function () use ($modelClass) {
            return $modelClass::count();
        });

        // 5. Validation Logic Enforcement
        if ((int)$currentCount >= (int)$limit) {
            $message = __("Limit reached! Maximum allowed: :limit", ['limit' => $limit]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message
                ], 403);
            }

            return redirect()->back()->with('error', $message);
        }

        return $next($request);
    }
}