<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use App\Jobs\Landlord\MigrateSingleTenantJob;
use App\Jobs\Landlord\MigrateTenantJob;
use App\Models\landlord\LandlordSetting;
use App\Models\landlord\Tenant;
use App\Traits\HasFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SettingsController extends Controller
{
    use HasFiles;
    public function generalSettings()
    {
        $settings = LandlordSetting::all()->pluck('value', 'key')->toArray();
        return view('landlord.dashboard.settings.landlord-settings', compact('settings'));
    }

    public function updateGeneralSettings(Request $request)
    {
        //dd($request->all());
        $validateData = $request->validate([
            'company_name' => 'required|string|max:255',
            'site_title' => 'required|string|max:255',
            'company_email' => 'required|email|max:255',
            'company_phone' => 'required|string|max:20',
            'company_address' => 'nullable|string|max:500',
            'company_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'favicon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,ico|max:1024',
        ]);

        foreach ($validateData as $key => $value) {
            if (in_array($key, ['company_logo', 'favicon']) && $request->hasFile($key)) {
                $oldPath = LandlordSetting::get($key);
                $newPath = $this->updateFile($request, $key, $oldPath, 'landlord/settings');
                LandlordSetting::set($key, $newPath);
                continue;
            }

            LandlordSetting::set($key, $value, 'general');
        }

        return back()->with('success', 'General settings updated successfully.');
    }

    public function updateEmailSettings(Request $request)
    {
        //dd($request->all());
        $validateData = $request->validate([
            'mail_host' => 'required|string|max:255',
            'mail_port' => 'required|integer',
            'mail_username' => 'required|string|max:255',
            'mail_password' => 'required|string|max:255',
            'mail_encryption' => 'nullable|string|max:50',
            'mail_from_address' => 'required|email|max:255',
        ]);

        foreach ($validateData as $key => $value) {
            LandlordSetting::set($key, $value, 'email');
        }

        return back()->with('success', 'Email settings updated successfully.');
    }

    public function updateSeoSettings(Request $request)
    {
        //dd($request->all());
        $validateData = $request->validate([
            'meta_title' => 'nullable|string|max:255',
            'meta_keywords' => 'nullable|string|max:500',
            'meta_description' => 'nullable|string|max:1000',
        ]);

        foreach ($validateData as $key => $value) {
            LandlordSetting::set($key, $value, 'seo');
        }

        return back()->with('success', 'SEO settings updated successfully.');
    }

    public function updateAnalyticsSettings(Request $request)
    {
        //dd($request->all());
        $validateData = $request->validate([
            'google_tag' => 'nullable|string|max:5000',
            'facebook_pixel' => 'nullable|string|max:5000',
            'chat_script' => 'nullable|string|max:5000',
        ]);
        foreach ($validateData as $key => $value) {
            LandlordSetting::set($key, $value, 'analytics');
        }

        return back()->with('success', 'Analytics settings updated successfully.');
    }

    public function updateLandlordDB()
    {
        try {
            Artisan::call('migrate', [
                '--database' => 'sherazipos_landlord',
                '--force' => true,
            ]);
            Artisan::call('db:seed', [
                '--database' => 'sherazipos_landlord',
                '--force' => true,
            ]);
            Artisan::call('config:clear');
            return redirect()->back()->with('success', 'Landlord database updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error updating landlord database: ' . $e->getMessage());
        }
    }

    public function updateTenantDB()
    {
        // try{
        //     // Tenant::select('id')
        //     //     ->orderBy('id')
        //     //     ->chunk(500, function ($tenants) {
        //     //         foreach ($tenants as $tenant) {
        //     //             dispatch(new MigrateTenantJob($tenant->id))
        //     //                 ->onQueue('tenant-migration');
        //     //         }
        //     //     });
            dispatch(new MigrateTenantJob());
            return back()->with('success', 'Database update started in background.');
        //     // $tenants = Tenant::all();
        //     // if(count($tenants)){
        //     //     Artisan::call('tenants:migrate',[
        //     //         '--force' => true
        //     //     ]);
        //     //     Artisan::call('tenants:seed');
        //     //     return redirect()->back()->with('success', 'Tenant database updated successfully.');
        //     // }else{
        //     //     return redirect()->back()->with('error', 'No tenants found.');
        //     // }
        // }catch(\Exception $e){
        //     return back()->with('error', 'Error updating tenant database: ' . $e->getMessage());
        // }
    }
}
