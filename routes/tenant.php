<?php

declare(strict_types=1);

use App\Http\Controllers\Accounting\AccountsController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\BillerController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CRM\LeadPublicFormController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerGroupController;
use App\Http\Controllers\CustomFieldController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GenericController;
use App\Http\Controllers\GlobalTrashController;
use App\Http\Controllers\LeadSubjectController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductSearchController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicFormController;
use App\Http\Controllers\PublicFormResponseController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\RackShelfController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\StorePurchaseController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\TenantMediaController;
use App\Http\Controllers\TenantPublicFormController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UnitGroupController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

Route::middleware([InitializeTenancyByDomain::class, PreventAccessFromCentralDomains::class,])->group(function () {
    Route::middleware(['web'])->group(function () {

        Route::middleware(['auth'])->group(function () {
            Route::get('/getDivisions', [SetupController::class, 'getDivisions'])->name('setup.getDivisions');
            Route::get('/getDistricts/{id}', [SetupController::class, 'getDistricts'])->name('setup.getDistricts');
            Route::get('/getUpazillas/{id}', [SetupController::class, 'getUpazillas'])->name('setup.getUpazillas');
            Route::get('/getUnions/{id}', [SetupController::class, 'getUnions'])->name('setup.getUnions');

                Route::get('/clear-cache', function () {
                    Cache::tags([tenant_tag()])->flush();
                    Artisan::call('view:clear');
                    dd('Cache cleared successfully.');
                })->name('tenant.clear-cache');

            Route::middleware(['tenant.status_check', 'check_setup_status'])->group(function () {
                Route::controller(SetupController::class)->group(function () {

                    Route::get('/setup', 'index')->name('setup.index');
                    Route::post('/setup', 'storeInitial')->name('setup.store');

                    Route::get('/setup/regional', 'regional')->name('setup.regional');
                    Route::post('/setup/regional', 'storeRegional')->name('setup.regional.store');

                    Route::get('/setup/accounting', 'accounting')->name('setup.accounting');
                    Route::post('/setup/accounting', 'storeAccounting')->name('setup.accounting.store');

                    // Route::get('/setup/opening-balance', 'openingBalance')->name('setup.opening-balance');
                    // Route::post('/setup/opening-balance', 'storeOpeningBalance')->name('setup.opening-balance.store');

                    Route::get('/setup/branch', 'branch')->name('setup.branch');
                    Route::post('/setup/branch', 'storeBranch')->name('setup.branch.store');

                    Route::get('/setup/complete', 'completeSetup')->name('setup.complete');
                    Route::post('/setup/complete', 'storeCompleteSetup')->name('setup.complete.store');
                });
                //Dashboard Routes
                Route::controller(DashboardController::class)->group(function () {
                    Route::get('/', 'index');
                    Route::get('/dashboard', 'dashboard')->name('dashboard');
                });

                Route::get('lang/{locale}', function ($locale) {
                    $locales = array_keys(config('locales'));

                    if (in_array($locale, $locales)) {
                        session(['locale' => $locale]);
                    }
                    return redirect()->back();
                })->name('tanant.switchLang');



                Route::controller(StorePurchaseController::class)->prefix('sherazipos-store')->middleware('permission:manage_store_purchase')->group(function () {
                    Route::get('/', 'index')->name('store-purchase');
                    Route::get('get-module-details/{moduleId}', 'getModuleDetails')->name('store-purchase.get-module-details');
                    Route::post('make-payment', 'makePayment')->name('store-purchase.make-payment');
                });

                Route::post('/upload-quill-image', [TenantMediaController::class, 'uploadQuillImage'])->name('upload.quill.image');
                Route::post('/upload-digital-file', [TenantMediaController::class, 'largeFileStore'])->name('upload.largeFileUpload');
                Route::patch('/upload-digital-file/{id}', [TenantMediaController::class, 'largeFileUpdate'])->name('upload.largeFileUpdate');

                Route::get('activity-log', [ActivityLogController::class, 'index'])->middleware('permission:manage_activity_log')->name('activity-log');
                Route::get('activity-log/{id}', [ActivityLogController::class, 'details'])->middleware('permission:manage_activity_log')->name('activity-log.details');
                Route::post('activity-log/clear', [ActivityLogController::class, 'clear'])->middleware('role:Super Admin')->name('activity-log.clear');

                Route::controller(ProfileController::class)->prefix('profile')->group(function () {
                    Route::get('/', 'index')->name('profile');
                    Route::put('/update', 'update')->name('profile.update');
                });

                Route::controller(GlobalTrashController::class)->prefix('trashes')->middleware('permission:manage_trash')->group(function () {
                    Route::get('/', [GlobalTrashController::class, 'index'])->name('trash.index');
                    Route::post('/restore/{id}', [GlobalTrashController::class, 'restore'])->name('trash.restore');
                    Route::delete('/permanent-delete/{id}', [GlobalTrashController::class, 'permanentDelete'])->name('trash.permanent-delete');
                    Route::post('/bulk-action', [GlobalTrashController::class, 'bulkAction'])->name('trash.bulk-action');
                });



                Route::controller(UserController::class)->middleware('permission:manage_user')->group(function () {
                    Route::get('/users', 'index')->name('users');
                    Route::get('/users/create', 'create')->name('users.create');
                    Route::post('/users', 'store')->name('users.store');
                    Route::get('/users/edit/{user}', 'edit')->name('users.edit');
                    Route::patch('/users/update/{user}', 'update')->name('users.update');
                    Route::delete('/users/delete/{user}', 'destroy')->name('users.destroy');
                    Route::post('bulk-delete-users', 'bulkDelete')->name('users.bulk-delete');
                });
                Route::controller(RolePermissionController::class)->middleware('permission:manage_role')->group(function () {
                    Route::get('/roles-permissions', 'index')->name('roles-permissions');
                    Route::post('/roles-permissions', 'store')->name('roles-permissions.store');
                    Route::patch('/roles-permissions/update', 'update')->name('roles-permissions.update');
                    Route::get('/manage-permissions/{role}', 'managePermissions')->name('manage-permissions');
                    Route::patch('/assign-permissions/{role}', 'assignPermissions')->name('assign-permissions');
                    Route::delete('/roles-permissions/{role}', 'destroy')->name('roles-permissions.destroy');
                });

                Route::controller(SettingsController::class)->prefix('settings')->group(function () {
                    Route::get('/', 'index')->name('settings');
                    Route::PUT('/general-settings', 'generalSettingsUpdate')->middleware('permission:manage_general_settings')->name('general-settings.update');
                    Route::post('/email-settings', 'updateEmailSettings')->middleware('permission:manage_email_settings')->name('email-settings.update');
                    Route::post('/sms-settings', 'updateSmsSettings')->middleware('permission:manage_sms_settings')->name('sms-settings.update');
                    Route::post('/currency-settings', 'updateCurrencySettings')->middleware('permission:manage_currency_settings')->name('currency-settings.update');
                    Route::post('/analytics-settings', 'updateAnalyticsSettings')->middleware('permission:manage_analytics_settings')->name('analytics-settings.update');
                    Route::post('/ai-settings', 'updateAiSettings')->middleware('permission:manage_ai_settings')->name('ai-settings.update');
                    Route::post('/currency-settings/sync-rate-now', 'syncRatesNow')->middleware('permission:manage_currency_settings')->name('currency-settings.sync-rate-now');
                    Route::get('/currency-settings/get-rates', 'getCurrencyRatesForModal')->name('currency-settings.get-rates');
                });

                Route::controller(BranchController::class)->prefix('branches')->middleware('check_active:branches_active')->group(function () {
                    Route::get('/', 'index')->middleware('permission:branch_view')->name('branches.index');
                    Route::post('/', 'store')->middleware(['permission:branch_create', 'check_limit:branches_limit,Branch'])->name('branches.store');
                    Route::get('/edit/{branch}', 'edit')->middleware('permission:branch_update')->name('branches.edit');
                    Route::patch('/update/{branch}', 'update')->middleware('permission:branch_update')->name('branches.update');
                    Route::delete('/delete/{branch}', 'destroy')->middleware('permission:branch_delete')->name('branches.destroy');
                });

                Route::controller(CategoryController::class)->prefix('categories')->middleware('check_active:products_active')->group(function () {
                    Route::get('/', 'index')->middleware('permission:category_view')->name('categories.index');
                    Route::get('get-categories-by-type/{type}', 'getCategoriesByType')->name('categories.getCategoriesByType');
                    Route::post('/', 'store')->middleware(['permission:category_create', 'check_limit:categories_limit,Category'])->name('categories.store');
                    Route::get('/edit/{category}', 'edit')->middleware('permission:category_update')->name('categories.edit');
                    Route::patch('/update/{category}', 'update')->middleware('permission:category_update')->name('categories.update');
                    Route::delete('/delete/{category}', 'destroy')->middleware('permission:category_delete')->name('categories.destroy');
                    Route::post('/bulk-delete', 'bulkDelete')->middleware('permission:category_delete')->name('categories.bulk-delete');
                    Route::get('/get-categories-by-status-type/{type}', 'getCategoriesByStatusType')->name('categories.getCategoriesByStatusType');
                });

                Route::controller(BrandController::class)->prefix('brands')->middleware('check_active:products_active')->group(function () {
                    Route::get('/', 'index')->middleware('permission:brand_view')->name('brands.index');
                    Route::post('/', 'store')->middleware(['permission:brand_create', 'check_limit:brands_limit,Brand'])->name('brands.store');
                    Route::get('/edit/{brand}', 'edit')->middleware('permission:brand_update')->name('brands.edit');
                    Route::patch('/update/{brand}', 'update')->middleware('permission:brand_update')->name('brands.update');
                    Route::delete('/delete/{brand}', 'destroy')->middleware('permission:brand_delete')->name('brands.destroy');
                    Route::post('/bulk-delete', 'bulkDelete')->middleware('permission:brand_delete')->name('brands.bulk-delete');
                });

                Route::controller(GenericController::class)->prefix('products')->middleware(['check_active:pharmacy_active', 'permission:products_generic_manage'])->group(function () {
                    Route::get('/generics', 'index')->name('generics.index');
                    Route::post('/generics', 'store')->name('generics.store');
                    Route::get('/generics/edit/{generic}', 'edit')->name('generics.edit');
                    Route::patch('/generics/update/{generic}', 'update')->name('generics.update');
                    Route::delete('/generics/delete/{generic}', 'destroy')->name('generics.destroy');
                    Route::post('/generics/bulk-delete', 'bulkDelete')->name('generics.bulk-delete');
                });

                Route::controller(RackShelfController::class)->prefix('racks-shelves')->middleware('check_active:rack_and_shelfs_active')->group(function () {
                    Route::get('/', 'index')->middleware('permission:rack_and_shelfs_view')->name('racks-shelves.index');
                    Route::post('/', 'store')->middleware(['permission:rack_and_shelfs_create', 'check_limit:racks_and_shelfs_limit,RackShelf'])->name('racks-shelves.store');
                    Route::get('/view/{rack}', 'show')->middleware('permission:rack_and_shelfs_view')->name('racks-shelves.view');
                    Route::get('/edit/{rack}', 'edit')->middleware('permission:rack_and_shelfs_update')->name('racks-shelves.edit');
                    Route::patch('/update/{rack}', 'update')->middleware('permission:rack_and_shelfs_update')->name('racks-shelves.update');
                    Route::delete('/delete/{rack}', 'destroy')->middleware('permission:rack_and_shelfs_delete')->name('racks-shelves.destroy');
                    Route::post('/bulk-delete', 'bulkDelete')->middleware('permission:rack_and_shelfs_delete')->name('racks-shelves.bulk-delete');
                });

                Route::controller(TaxController::class)->prefix('taxes')->middleware('permission:manage_vat_tax')->group(function () {
                    Route::get('/', 'index')->name('taxes.index');
                    Route::post('/', 'store')->name('taxes.store');
                    Route::get('/edit/{tax}', 'edit')->name('taxes.edit');
                    Route::patch('/update/{tax}', 'update')->name('taxes.update');
                    Route::delete('/delete/{tax}', 'destroy')->name('taxes.destroy');
                });
                // units and unit groups routes
                Route::prefix('units')->middleware('check_active:products_active')->group(function () {
                    Route::controller(UnitController::class)->group(function () {
                        Route::get('/', 'index')->middleware('permission:unit_view')->name('units.index');
                        Route::post('/', 'store')->middleware(['permission:unit_create'])->name('units.store');
                        Route::get('/edit/{unit}', 'edit')->middleware('permission:unit_update')->name('units.edit');
                        Route::patch('/update/{unit}', 'update')->middleware('permission:unit_update')->name('units.update');
                        Route::delete('/delete/{unit}', 'destroy')->middleware('permission:unit_delete')->name('units.destroy');
                        Route::get('/get-sub-units/{unit}', 'getSubUnits')->name('units.getSubUnits');
                    });

                    Route::controller(UnitGroupController::class)->group(function () {
                        Route::get('/unit-groups', 'index')->middleware('permission:unit_view')->name('unit-groups.index');
                        Route::post('/unit-gorup/create', 'store')->middleware('permission:unit_create')->name('unit-groups.store');
                        Route::get('/unit-group/edit/{unitGroup}', 'edit')->middleware('permission:unit_update')->name('unit-groups.edit');
                        Route::patch('/unit-group/update/{unitGroup}', 'update')->middleware('permission:unit_update')->name('unit-groups.update');
                        Route::delete('/unit-group/delete/{unitGroup}', 'destroy')->middleware('permission:unit_delete')->name('unit-groups.destroy');
                        Route::get('/get-units-by-unit-group/{unitGroup}', 'getUnitsByGroup')->name('units.getUnitsByGroup');
                        Route::get('/get-base-units-by-unit-group/{unitGroup}', 'getBaseUnitsByGroup')->name('units.getBaseUnitsByGroup');
                    });
                });

                // Customer Group Routes
                Route::middleware('check_active:customers_active')->group(function () {
                    Route::controller(CustomerGroupController::class)->prefix('customer-groups')->middleware('permission:manage_customer_group')->group(function () {
                        Route::get('/', 'index')->name('customer_groups.index');
                        Route::post('/', 'store')->name('customer_groups.store');
                        Route::get('/edit/{customerGroup}', 'edit')->name('customer_groups.edit');
                        Route::patch('/update/{customerGroup}', 'update')->name('customer_groups.update');
                        Route::delete('/delete/{customerGroup}', 'destroy')->name('customer_groups.destroy');
                        Route::post('/bulk-delete', 'bulkDelete')->name('customer_groups.bulk-delete');
                    });
                });

                Route::controller(MembershipController::class)->prefix('memberships')->middleware(['check_active:membership_active', 'permission:manage_membership'])->group(function () {
                    Route::get('/', 'index')->name('memberships.index');
                    Route::post('/', 'store')->name('memberships.store');
                    Route::get('/edit/{membership}', 'edit')->name('memberships.edit');
                    Route::patch('/update/{membership}', 'update')->name('memberships.update');
                    Route::delete('/delete/{membership}', 'destroy')->name('memberships.destroy');
                });

                Route::controller(CustomerController::class)->prefix('customers')->middleware('check_active:customers_active')->group(function () {
                    Route::get('/', 'index')->middleware('permission:customer_view')->name('customers.index');
                    Route::post('/', 'store')->middleware(['permission:customer_create', 'check_limit:customers_limit,Customer'])->name('customers.store');
                    Route::get('/show/{customer}', 'show')->middleware('permission:customer_view')->name('customers.show');
                    Route::get('/edit/{customer}', 'edit')->middleware('permission:customer_update')->name('customers.edit');
                    Route::patch('/update/{customer}', 'update')->middleware('permission:customer_update')->name('customers.update');
                    Route::delete('/delete/{customer}', 'destroy')->middleware('permission:customer_delete')->name('customers.destroy');
                    Route::post('/bulk-delete', 'bulkDelete')->middleware('permission:customer_delete')->name('customers.bulk-delete');
                    Route::get('/import', 'import')->middleware('permission:customer_create')->name('customers.import');
                    Route::post('/import', 'importStore')->middleware('permission:customer_create')->name('customers.importStore');
                    Route::get('/export', 'export')->middleware('permission:customer_view')->name('customers.export');
                });

                Route::controller(SupplierController::class)->prefix('suppliers')->middleware('check_active:suppliers_active')->group(function () {
                    Route::get('/', 'index')->middleware('permission:supplier_view')->name('suppliers.index');
                    Route::post('/', 'store')->middleware(['permission:supplier_create', 'check_limit:suppliers_limit,Supplier'])->name('suppliers.store');
                    Route::get('/show/{supplier}', 'show')->middleware('permission:supplier_view')->name('suppliers.show');
                    Route::get('/edit/{supplier}', 'edit')->middleware('permission:supplier_update')->name('suppliers.edit');
                    Route::patch('/update/{supplier}', 'update')->middleware('permission:supplier_update')->name('suppliers.update');
                    Route::delete('/delete/{supplier}', 'destroy')->middleware('permission:supplier_delete')->name('suppliers.destroy');
                    Route::post('/bulk-delete', 'bulkDelete')->middleware('permission:supplier_delete')->name('suppliers.bulk-delete');
                    Route::get('/import', 'import')->middleware('permission:supplier_create')->name('suppliers.import');
                    Route::post('/import', 'importStore')->middleware(['permission:supplier_create', 'check_limit:suppliers_limit,Supplier'])->name('suppliers.importStore');
                    Route::get('/export', 'export')->middleware('permission:supplier_view')->name('suppliers.export');
                });

                Route::controller(BillerController::class)->prefix('billers')->middleware('check_active:billers_active')->group(function () {
                    Route::get('/', 'index')->middleware('permission:billers_view')->name('billers.index');
                    Route::post('/', 'store')->middleware(['permission:billers_create', 'check_limit:billers_limit,Biller'])->name('billers.store');
                    Route::get('/show/{biller}', 'show')->middleware('permission:billers_view')->name('billers.show');
                    Route::get('/edit/{biller}', 'edit')->middleware('permission:billers_update')->name('billers.edit');
                    Route::patch('/update/{biller}', 'update')->middleware('permission:billers_update')->name('billers.update');
                    Route::delete('/delete/{biller}', 'destroy')->middleware('permission:billers_delete')->name('billers.destroy');
                    Route::post('/bulk-delete', 'bulkDelete')->middleware('permission:billers_delete')->name('billers.bulk-delete');
                    Route::get('/import', 'import')->middleware('permission:billers_create')->name('billers.import');
                    Route::post('/import', 'importStore')->middleware(['permission:billers_create', 'check_limit:billers_limit,Biller'])->name('billers.importStore');
                    Route::get('/export', 'export')->middleware('permission:billers_view')->name('billers.export');
                });

                Route::controller(AddressController::class)->group(function () {
                    Route::get('/address-lookup', 'lookup')->name('address.lookup');
                });


                Route::controller(ProductController::class)->prefix('products')->middleware('check_active:products_active')->group(function () {
                    Route::get('/', 'index')->middleware('permission:products_view')->name('products.index');
                    Route::get('/create', 'create')->middleware('permission:products_create')->name('products.create');
                    Route::post('/', 'store')->middleware(['permission:products_create', 'check_limit:products_limit,Product'])->name('products.store');
                    Route::get('/show/{product}', 'show')->middleware('permission:products_view')->name('products.show');
                    Route::get('/edit/{product}', 'edit')->middleware('permission:products_update')->name('products.edit');
                    Route::patch('/update/{product}', 'update')->middleware('permission:products_update')->name('products.update');
                    Route::delete('/delete/{product}', 'destroy')->middleware('permission:products_delete')->name('products.destroy');
                    Route::delete('/products/gallery-image-remove', 'removeGalleryImage')->name('products.gallery.remove');
                    Route::post('/bulk-delete', 'bulkDelete')->middleware('permission:products_delete')->name('products.bulk-delete');
                    Route::get('/manage-variants/{product}', 'manageVariants')->middleware('permission:products_create|products_update')->name('products.variants.manage');
                    Route::post('/manage-variants/{product}', 'updateVariants')->middleware('permission:products_create|products_update')->name('products.variants.update');
                    Route::get('/import', 'import')->middleware('permission:products_import')->name('products.import');
                    Route::post('/import', 'importStore')->middleware(['permission:products_import', 'check_limit:products_limit,Product'])->name('products.importStore');
                    Route::get('/export', 'export')->middleware('permission:products_export')->name('products.export');
                    Route::get('/generate-itemcode', 'generateItemCode')->name('products.generateItemCode');
                    Route::get('/opening-stock-manage/{product}', 'openingStockManage')->middleware('permission:products_create|products_update')->name('products.openingStock.manage');
                    Route::post('/opening-stock-manage/{product}', 'openingStockUpdate')->middleware('permission:products_create|products_update')->name('products.openingStock.update');
                });

                Route::controller(ProductSearchController::class)->group(function () {
                    Route::get('products/get-all-products', 'getAllProducts')->name('products.getAllProducts');
                    Route::get('products/search-for-combo', 'searchForCombo')->name('products.searchForCombo');
                });

                Route::controller(AttributeController::class)->prefix('attributes')->middleware('permission:manage_attributes')->group(function () {
                    Route::get('/', 'index')->name('attributes.index');
                    Route::post('/', 'store')->name('attributes.store');
                    Route::get('/edit/{attribute}', 'edit')->name('attributes.edit');
                    Route::patch('/update/{attribute}', 'update')->name('attributes.update');
                    Route::delete('/delete/{attribute}', 'destroy')->name('attributes.destroy');
                });

                Route::controller(TenantPublicFormController::class)->prefix('public-forms')->group(function () {
                    Route::get('/', 'index')->name('public-forms.index');
                    Route::get('/create', 'create')->name('public-forms.create');
                    Route::get('public-forms/get-fields', 'getFieldsByType')->name('public-forms.get-fields');
                    Route::post('/', 'store')->name('public-forms.store');
                    Route::get('/{publicForm}/edit', 'edit')->name('public-forms.edit');
                    Route::put('/{publicForm}', 'update')->name('public-forms.update');
                    Route::delete('/{publicForm}', 'destroy')->name('public-forms.destroy');
                    Route::post('/bulk-delete', 'bulkDelete')->name('public-forms.bulk-delete');
                    Route::patch('/{publicForm}/toggle', 'toggle')->name('public-forms.toggle');
                    Route::post('/{id}/token', 'generateTokenizedLink')->middleware('throttle:10,1')->name('public-forms.tokens.store');
                });

                // public forms responses
                Route::controller(PublicFormResponseController::class)->prefix('public-forms-responses')->group(function () {
                    Route::get('/{id}', 'index')->name('public-forms-responses.index');
                    Route::get('/{id}/show', 'show')->name('public-forms-responses.show');
                    Route::delete('/{publicFormResponse}/delete', 'destroy')->name('public-forms-responses.destroy');
                    Route::post('/bulk-delete', 'bulkDelete')->name('public-forms-responses.bulk-delete');

                });

                Route::controller(CustomFieldController::class)->prefix('custom-fields')->middleware('permission:manage_custom_fields')->group(function () {
                    Route::get('/', 'index')->name('custom-fields.index');
                    Route::post('/', 'store')->name('custom-fields.store');
                    Route::get('/edit/{customField}', 'edit')->name('custom-fields.edit');
                    Route::patch('/update/{customField}', 'update')->name('custom-fields.update');
                    Route::delete('/delete/{customField}', 'destroy')->name('custom-fields.destroy');
                });

                Route::controller(PurchaseController::class)->prefix('purchases')->middleware('check_active:purchases_active')->group(function () {
                    Route::get('/', 'index')->middleware('permission:purchase_view')->name('purchases.index');
                    Route::get('/create', 'create')->middleware('permission:purchase_create')->name('purchases.create');
                    Route::post('/', 'store')->middleware(['permission:purchase_create', 'check_limit:purchases_limit,Purchase'])->name('purchases.store');
                    Route::get('/{purchase}/show', 'show')->middleware('permission:purchase_view')->name('purchases.show');
                    Route::get('/{purchase}/edit', 'edit')->middleware('permission:purchase_update')->name('purchases.edit');
                    Route::put('/{purchase}/update', 'update')->middleware('permission:purchase_update')->name('purchases.update');
                    Route::delete('/{purchase}/delete', 'destroy')->middleware('permission:purchase_delete')->name('purchases.destroy');
                });

                Route::controller(AssetController::class)->prefix('asset')->middleware('check_active:assets_active')->group(function(){
                    Route::get('/', 'index')->middleware('permission:assets_view')->name('assets.index');
                    Route::post('/', 'store')->middleware(['permission:assets_create', 'check_limit:assets_limit,Asset'])->name('assets.store');
                    Route::delete('/delete/{asset}', 'destroy')->middleware('permission:assets_delete')->name('assets.destroy');

                    Route::get('/register/list', 'assetRegisterIndex')->middleware('permission:assets_register_manage')->name('assets.register.index');
                    Route::get('/register', 'createAssetRegister')->middleware('permission:assets_register_manage')->name('assets.register.create');
                    Route::post('/register', 'storeAssetRegister')->middleware('permission:assets_register_manage')->name('assets.register.store');
                    Route::get('/register/{register}', 'showAssetRegister')->middleware('permission:assets_register_manage')->name('assets.register.show');
                    Route::delete('/register/delete/{register}', 'destroyRegister')->middleware('permission:assets_register_manage')->name('assets.register.destroy');
                });
                
                require __DIR__ . '/accounting.php';
                require __DIR__ . '/crm.php';
            });
        });

        // lead public form routes
        Route::controller(PublicFormController::class)->middleware('throttle:3,1')->group(function () {
            Route::post('/form/{slug}/t/{token}', 'submit')->name('public-forms.submit');
        });
        Route::get('/form/{slug}/t/{token}', [PublicFormController::class, 'show'])->name('public-forms.show');

        Route::controller(LeadPublicFormController::class)->prefix('lead-public-form')->group(function () {
            Route::get('/', 'index')->name('lead-public-form.index');
            Route::post('/submit', 'submitLead')->name('lead-public-form.submit');
        });


        require __DIR__ . '/auth.php';
    });
});
