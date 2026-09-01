<?php

use App\Services\FeatureService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

if (!function_exists('getTenantMenu')) {
    function getTenantMenu() {
        $menus = config('tenant_menu');
        $user = Auth::user();
        $features = FeatureService::getAllFeature();

        return collect($menus)->map(function ($menu) use ($user, $features) {
            if (isset($menu['sub_menu'])) {
                $menu['sub_menu'] = collect($menu['sub_menu'])->filter(function($sub) use ($user, $features) {
                    $fActive = !isset($sub['feature']) || ($features[$sub['feature']] ?? '0') == '1';
                    $pCheck = !isset($sub['permission']) || $user->canAny($sub['permission']);
                    return $fActive && $pCheck;
                })->toArray();
            }
            return $menu;
        })->filter(function ($menu) use ($user, $features) {
            $fActive = !isset($menu['feature']) || ($features[$menu['feature']] ?? '0') == '1';
            $pCheck = !isset($menu['permission']) || $user->canAny($menu['permission']);
            
            if (isset($menu['sub_menu']) && empty($menu['sub_menu'])) return false;

            return $fActive && $pCheck;
        })->toArray();
    }
}