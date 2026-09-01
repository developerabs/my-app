<?php

use App\Http\Middleware\CheckFeatureActive;
use App\Http\Middleware\CheckFeatureLimit;
use App\Http\Middleware\CheckSetupStatus;
use App\Http\Middleware\LandlordAuth;
use App\Http\Middleware\RedirectWwwToNonWww;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\TenantSubscriptionCheckMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\App;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant.status_check' => TenantSubscriptionCheckMiddleware::class,
            'check_setup_status' => CheckSetupStatus::class,
            'landlord' => LandlordAuth::class,
            'check_active' => CheckFeatureActive::class,
            'check_limit' => CheckFeatureLimit::class,

            'permission' => PermissionMiddleware::class,
            'role' => RoleMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        $middleware->priority([
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\Auth\Middleware\Authenticate::class,
            \App\Http\Middleware\LandlordAuth::class, // ল্যান্ডলর্ড চেক আগে হবে
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \Illuminate\Auth\Middleware\Authorize::class, // এটি গেট রান করবে
        ]);

        $middleware->web(append: [
            SetLocale::class,
            RedirectWwwToNonWww::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
