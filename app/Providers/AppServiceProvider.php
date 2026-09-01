<?php

namespace App\Providers;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Observers\BranchObserver;
use App\Observers\ProductObserver;
use App\Observers\ProductVariantObserver;
use App\Services\FeatureService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\PermissionRegistrar;

use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }
    public function boot(): void
    {
        // Set Tenant ID


        // Check for permission cache
        // if (!app()->runningInConsole()) {
        //     try {
        //         $cacheKey = config('permission.cache.key');
        //         if (is_string($cacheKey) && !Cache::store('redis')->has($cacheKey)) {
        //             app(PermissionRegistrar::class)->forgetCachedPermissions();
        //         }
        //     } catch (\Exception $e) {
        //         Log::error("Permission Reset Error: " . $e->getMessage());
        //     }
        // }

        // Allow Super-Admin to access all routes
        Gate::before(function ($user, $ability) {
            // directly return true to pass the permission check
            // if ($user->roles->contains('name', 'Super Admin')) {
            //     return true;
            // }
            return $user->hasRole('Super Admin') ? true : null;
        });

        Blade::if('feature', function ($featureKey) {
            return is_feature_active($featureKey);
        });

        Blade::if('featureCan', function ($featureKey, $permissionName) {
            return is_feature_active($featureKey) 
                && auth()->check() 
                && auth()->user()->can($permissionName);
        });


        Product::observe(ProductObserver::class);
        ProductVariant::observe(ProductVariantObserver::class);
        Branch::observe(BranchObserver::class);


        // লারাভেলের বুট সাইকেলের শেষ মুহূর্তে মডিউলের মাইগ্রেশন পাথগুলো ফিল্টার করা হচ্ছে
        $this->app->booted(function ($app) {
            $migrator = $app['migrator'];

            // ১. চেক করা হচ্ছে এটি কোনো টেনান্টের রিকোয়েস্ট বা কমান্ড কি না (Stancl/Tenancy অনুযায়ী)
            // অথবা চলমান কমান্ড বা প্রসেসের কোথাও 'tenant' শব্দটি আছে কি না
            $rawArgs = request()->server('argv') ?? [];
            $commandString = implode(' ', $rawArgs);

            $isTenantContext = tenancy()->initialized ||
                Str::contains($commandString, 'tenant') ||
                Str::contains(request()->url(), 'tenant');

            // ২. যদি এটি টেনান্টের কোনো কনটেক্সট না হয় (তার মানে এটি ১০০% ল্যান্ডলর্ড প্রসেস)
            if (!$isTenantContext) {
                $paths = $migrator->paths();

                // 'Modules' ফোল্ডারের ভেতরের সব মাইগ্রেশন পাথ ফিল্টার করে বাদ দেওয়া হচ্ছে
                $filteredPaths = array_filter($paths, function ($path) {
                    return !Str::contains($path, base_path('Modules'));
                });

                // রিfলেক্টরের মাধ্যমে জোরপূর্বক শুধুমাত্র ল্যান্ডলর্ড বা রুট প্রজেক্টের পাথগুলো সেট করা হলো
                $reflection = new \ReflectionClass($migrator);
                $property = $reflection->getProperty('paths');
                $property->setAccessible(true);
                $property->setValue($migrator, array_values($filteredPaths));
            }
        });
    }
}
