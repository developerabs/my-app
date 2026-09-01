<?php

use App\Http\Controllers\Auth\LandlordLoginController;
use App\Http\Controllers\Landlord\AddonsController;
use App\Http\Controllers\Landlord\BillingController;
use App\Http\Controllers\Landlord\DashboardController;
use App\Http\Controllers\Landlord\FeatureController;
use App\Http\Controllers\Landlord\GatewayController;
use App\Http\Controllers\Landlord\HomePageController;
use App\Http\Controllers\Landlord\HomepageWidgetController;
use App\Http\Controllers\Landlord\LandlordProfileController;
use App\Http\Controllers\Landlord\LandlordRolePermissionController;
use App\Http\Controllers\Landlord\LandlordUserController;
use App\Http\Controllers\Landlord\LandlordWidgetController;
use App\Http\Controllers\Landlord\MediaController;
use App\Http\Controllers\Landlord\PackageController;
use App\Http\Controllers\Landlord\PageController;
use App\Http\Controllers\Landlord\BlogController;
use App\Http\Controllers\Landlord\ClientController;
use App\Http\Controllers\Landlord\CurrencyController;
use App\Http\Controllers\Landlord\ProposalController;
use App\Http\Controllers\Landlord\ResellerController;
use App\Http\Controllers\Landlord\SettingsController;
use App\Http\Controllers\Landlord\StorePurchaseController;
use App\Http\Controllers\Landlord\UploadController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Yajra\DataTables\Html\Columns\Index;

Route::controller(HomePageController::class)->group(function () {
    Route::get('/', 'index')->name('landlord.home');
    Route::get('pages/{slug}', 'viewPage')->name('landlord.view.page');
    Route::get('blogs/{slug}', 'viewBlog')->name('landlord.view.Blog');
});



Route::controller(LandlordLoginController::class)->group(function () {
    Route::get('superadmin-login', 'showLoginForm')->name('landlord.loginform');
    Route::post('superadmin-login', 'login')->name('landlord.login');
});

Route::get('/cache-test', function () {
    Cache::tags([landlord_tag()])->put('foo', 'bar', 600);

    return [
        'store' => config('cache.default'),
        'value' => Cache::tags([landlord_tag()])->get('foo'),
        'has'   => Cache::tags([landlord_tag()])->has('foo'),
    ];
});

Route::prefix('superadmin')->middleware('landlord')->group(function () {

    Route::get('/clear', function () {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        return redirect()->back()->with('success', 'Cache cleared successfully.');
    })->name('clear-cache');
    
    Route::post('/logout', [LandlordLoginController::class, 'logout'])->name('landlord.logout');
    Route::post('/upload-quill-image', [MediaController::class, 'uploadQuillImage'])->name('landlord.upload.image');

    Route::get('lang/{locale}', function ($locale) {
        $locales = array_keys(config('locales'));

        if (in_array($locale, $locales)) {
            session(['locale' => $locale]);
        }
        return redirect()->back();
    })->name('switchLang');

    Route::controller(DashboardController::class)->middleware('permission:dashboard_view')->group(function () {
        Route::get('/dashboard', 'index')->name('landlord.dashboard');
    });

    Route::controller(LandlordProfileController::class)->prefix('profile')->group(function () {
        Route::get('/', 'index')->name('landlord.myprofile');
        Route::PUT('/update', 'updateProfile')->name('landlord.myprofile.update');
        Route::PATCH('/change-password', 'changePassword')->name('landlord.myprofile.changePassword');
    });

    Route::controller(LandlordUserController::class)->middleware('permission:manage_user')->group(function () {
        Route::get('/users', 'index')->name('landlord.users');
        Route::get('/users/create', 'create')->name('landlord.users.create');
        Route::post('/users', 'store')->name('landlord.users.store');
        Route::get('/users/edit/{user}', 'edit')->name('landlord.users.edit');
        Route::patch('/users/update/{user}', 'update')->name('landlord.users.update');
        Route::delete('/users/delete/{user}', 'destroy')->name('landlord.users.destroy');
    });
    Route::controller(LandlordRolePermissionController::class)->middleware('permission:manage_role')->group(function () {
        Route::get('/roles-permissions', 'index')->name('landlord.roles-permissions');
        Route::post('/roles-permissions', 'store')->name('landlord.roles-permissions.store');
        Route::patch('/roles-permissions/update', 'update')->name('landlord.roles-permissions.update');
        Route::get('/manage-permissions/{role}', 'managePermissions')->name('landlord.manage-permissions');
        Route::patch('/assign-permissions/{role}', 'assignPermissions')->name('landlord.assign-permissions');
        Route::delete('/roles-permissions/{role}', 'destroy')->name('landlord.roles-permissions.destroy');
    });

    Route::controller(SettingsController::class)->group(function () {
        Route::get('/landlord-settings', 'generalSettings')->name('landlord.landlord-settings');
        Route::post('/general-settings', 'updateGeneralSettings')->middleware('permission:manage_general_setting')->name('landlord.general-settings.update');
        Route::post('/email-settings', 'updateEmailSettings')->middleware('permission:manage_email_setting')->name('landlord.email-settings.update');
        Route::post('/sms-settings', 'updateSmsSettings')->middleware('permission:manage_sms_setting')->name('landlord.sms-settings.update');
        Route::post('/seo-settings', 'updateSeoSettings')->middleware('permission:manage_seo_setting')->name('landlord.seo-settings.update');
        Route::post('/analytics-settings', 'updateAnalyticsSettings')->middleware('permission:manage_analytics_setting')->name('landlord.analytics-settings.update');
        Route::post('/ai-settings', 'updateAiSettings')->middleware('permission:manage_ai_setting')->name('landlord.ai-settings.update');
        Route::post('/update-landlord-db', 'updateLandlordDB')->middleware('permission:manage_database_update')->name('landlord.update-landlord-db');
        Route::post('/update-tenant-db', 'updateTenantDB')->middleware('permission:manage_database_update')->name('landlord.update-tenant-db');
    });

    Route::controller(GatewayController::class)->group(function () {
        Route::middleware('permission:manage_payment_setting')->group(function () {
            Route::get('/payment-gateway', 'paymentGateway')->name('landlord.payment-gateway');
            Route::post('/payment-gateway/store', 'storePaymentGateway')->name('landlord.payment-gateway.store');
            Route::get('/payment-gateway/edit/{gateway}', 'editPaymentGateway')->name('landlord.payment-gateway.edit');
            Route::patch('/payment-gateway/update', 'updatePaymentGateway')->name('landlord.payment-gateway.update');
            Route::delete('/payment-gateway/destroy/{gateway}', 'destroyPaymentGateway')->name('landlord.payment-gateway.destroy');
        });
        Route::post('/sms-gateway/store', 'storeSmsGateway')->middleware('permission:manage_sms_setting')->name('landlord.sms-gateway.store');
    });

    Route::controller(CurrencyController::class)->middleware('permission:manage_currency')->group(function () {
        Route::get('/currencies', 'index')->name('landlord.currencies');
        Route::post('/currencies', 'store')->name('landlord.currencies.store');
        Route::get('/currencies/edit/{currency}', 'edit')->name('landlord.currencies.edit');
        Route::patch('/currencies/update/{currency}', 'update')->name('landlord.currencies.update');
        Route::delete('/currencies/delete/{currency}', 'destroy')->name('landlord.currencies.destroy');
    });

    Route::controller(ClientController::class)->group(function () {
        Route::get('/clients', 'index')->middleware('permission:client_view')->name('landlord.clients');
        Route::get('/clients/create', 'create')->middleware('permission:client_create')->name('landlord.clients.create');
        Route::post('/clients', 'store')->middleware('permission:client_create')->name('landlord.clients.store');
        Route::delete('/clients/delete/{id}', 'destroy')->middleware('permission:client_delete')->name('landlord.clients.destroy');
        Route::get('/clientDues', 'clientDues')->middleware('permission:client_dues')->name('landlord.clientDues');
        Route::post('/clientNotes/store', 'storenote')->middleware('permission:client_notes')->name('landlord.clientNotes.store');
        Route::get('/landlord/client-notes/{id}', 'getClientNotes')->middleware('permission:client_notes')->name('landlord.getClientNotes');
    });


    Route::controller(ProposalController::class)->group(function () {
        Route::get('/proposals', 'index')->middleware('permission:proposal_view')->name('landlord.proposals');
        Route::get('/proposals/view/{proposal}', 'show')->middleware('permission:proposal_view')->name('landlord.proposals.view');
        Route::get('/proposals/create', 'create')->middleware('permission:proposal_create')->name('landlord.proposals.create');
        Route::post('/proposals', 'store')->middleware('permission:proposal_store')->name('landlord.proposals.store');
        Route::get('/proposals/edit/{proposal}', 'edit')->middleware('permission:proposal_edit')->name('landlord.proposal.edit');
        Route::patch('/proposals/update/{proposal}', 'update')->middleware('permission:proposal_update')->name('landlord.proposals.update');
        Route::delete('/proposals/delete/{proposal}', 'destroy')->middleware('permission:proposal_delete')->name('landlord.proposals.destroy');
    });

    Route::controller(FeatureController::class)->middleware('permission:manage_feature')->group(function () {
        Route::get('/features', 'index')->name('landlord.features');
    });

    Route::controller(PackageController::class)->middleware('permission:manage_package')->group(function () {
        Route::get('/packages', 'index')->name('landlord.packages');
        Route::get('/packages/create', 'create')->name('landlord.packages.create');
        Route::post('/packages', 'store')->name('landlord.packages.store');
        Route::get('/packages/edit/{package}', 'edit')->name('landlord.packages.edit');
        Route::patch('/packages/update/{package}', 'update')->name('landlord.packages.update');
        Route::patch('/packages/update-status/{package}', 'updateStatus')->name('landlord.packages.updateStatus');
        Route::delete('/packages/delete/{package}', 'destroy')->name('landlord.packages.destroy');
    });
    Route::get('get-package-info/{package}', [PackageController::class, 'getPackageInfo'])->name('landlord.getPackageInfo');

    Route::controller(AddonsController::class)->middleware('permission:manage_addons')->group(function () {
        Route::get('/addons', 'index')->name('landlord.addons');
        Route::post('/addons', 'store')->name('landlord.addons.store');
        Route::get('/addons/edit/{addon}', 'edit')->name('landlord.addons.edit');
        Route::patch('/addons/update/{addon}', 'update')->name('landlord.addons.update');
        Route::delete('/addons/delete/{addon}', 'destroy')->name('landlord.addons.destroy');
    });

    Route::controller(ResellerController::class)->group(function () {
        Route::get('/resellers', 'index')->middleware('permission:reseller_view')->name('landlord.resellers');
        Route::post('/resellers', 'store')->middleware('permission:reseller_create')->name('landlord.resellers.store');
        Route::get('/resellers/edit/{reseller}', 'edit')->middleware('permission:reseller_edit')->name('landlord.resellers.edit');
        Route::patch('/resellers/update/{reseller}', 'update')->middleware('permission:reseller_edit')->name('landlord.resellers.update');
        Route::delete('/resellers/delete/{reseller}', 'destroy')->middleware('permission:reseller_delete')->name('landlord.resellers.destroy');
    });

    Route::prefix('cms')->middleware('permission:manage_cms')->group(function () {
        Route::controller(PageController::class)->group(function () {
            Route::get('/pages', 'index')->name('landlord.pages');
            Route::get('/pages/create', 'create')->name('landlord.pages.create');
            Route::post('/pages', 'store')->name('landlord.pages.store');
            Route::get('/pages/edit/{page}', 'edit')->name('landlord.pages.edit');
            Route::patch('/pages/update/{page}', 'update')->name('landlord.pages.update');
            Route::delete('/pages/delete/{page}', 'destroy')->name('landlord.pages.destroy');
            Route::patch('/pages/update-status/{page}', 'updateStatus')->name('landlord.pages.updateStatus');
        });

        Route::controller(BlogController::class)->group(function () {
            Route::get('/blogs', 'index')->name('landlord.blogs');
            Route::get('/blogs/create', 'create')->name('landlord.blogs.create');
            Route::post('/blogs', 'store')->name('landlord.blogs.store');
            Route::get('/blogs/edit/{blog}', 'edit')->name('landlord.blogs.edit');
            Route::patch('/blogs/update/{blog}', 'update')->name('landlord.blogs.update');
            Route::delete('/blogs/delete/{blog}', 'destroy')->name('landlord.blogs.destroy');
            Route::patch('/blogs/update-status/{blog}', 'updateStatus')->name('landlord.blogs.updateStatus');
        });

        Route::controller(HomepageWidgetController::class)->group(function () {
            Route::get('/homepagewidget', 'index')->name('landlord.homepagewidget');
            Route::get('/get-widgets', 'getWidgets')->name('landlord.get-widgets');
            Route::post('/homepagewidget', 'store')->name('landlord.store-widget');
            Route::get('/configure-widget/{widget}', 'configureWidget')->name('landlord.configure-widget');
            Route::patch('/update-widget/{widget}', 'updateWidget')->name('landlord.update-widget');
            Route::delete('/delete-widget/{widget}', 'deleteWidget')->name('landlord.delete-widget');
        });
    });
});

$centralDomain = env('CENTRAL_DOMAIN', 'sherazipos12.localhost');
Route::domain($centralDomain)->group(function () {
    Route::controller(BillingController::class)->prefix('billing')->group(function () {
        Route::get('/{tenantId}', 'index')->name('billing');
        Route::get('/get-package-info/{tenantId}/{package}', 'getPackageInfo')->name('billing.getPackageInfo');
        Route::get('/billing/check-limits/{package}', 'checkPackageLimits')->name('billing.packageCheckLimits');
        Route::post('/checkout/{tenant}', 'checkout')->name('billing.checkout');
        Route::get('/success/{tenant}', 'paymentSuccess')->name('billing.success');
        Route::get('/cancel/{tenant}', 'paymentCancel')->name('billing.cancel');
        Route::get('/failed/{tenant}', 'paymentFailed')->name('billing.failed');
    });
});
