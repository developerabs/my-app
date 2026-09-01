<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

if (!function_exists('getLandlordMenu')) {
    function getLandlordMenu() {
        $menus = config('landlord_menu');
        $user = Auth::user();

        return collect($menus)->map(function ($menu) use ($user) {
            if (isset($menu['sub_menu'])) {
                $menu['sub_menu'] = collect($menu['sub_menu'])
                    ->filter(fn($submenu) => !isset($submenu['permission']) || $user->hasAnyPermission($submenu['permission']))
                    ->toArray();
            }
            return $menu;
        })->filter(function ($menu) use ($user) {
            if (isset($menu['sub_menu']) && count($menu['sub_menu']) === 0) {
                return false;
            }
            return !isset($menu['permission']) || $user->hasAnyPermission($menu['permission']);
        })->toArray();
    }
}

if (!function_exists('isActiveMenu')) {
    function isActiveMenu($menu) {
        $current = Route::currentRouteName();

        // যদি মেনুতে সাবমেনু থাকে, তবে যে কোনো সাবমেনুর route মিললে এটাকেও active ধরব
        if (isset($menu['sub_menu'])) {
            foreach ($menu['sub_menu'] as $submenu) {
                if ($current === ($submenu['route'] ?? '')) {
                    return true;
                }
            }
        }

        // মূল মেনুর route মিললে এটাও active হবে
        return $current === ($menu['route'] ?? '');
    }
}

