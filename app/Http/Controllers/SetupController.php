<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Currency;
use App\Models\Setting;
use App\Services\Accounting\AccountingIntegrationService;
use App\Services\Accounting\AccountingService;
use App\Services\Central\LandlordService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SetupController extends Controller
{
    public function __construct(
        protected LandlordService $landlordService,
        protected AccountingService $accountingService,
    ) {}

    public function index()
    {
        return view('setup.index');
    }

    public function storeInitial(Request $request)
    {
        $validated = $request->validate([
            'agree_tnc' => ['required', 'accepted'],
        ]);

        Setting::set([
            'setup_step' => 'regional',
            'agreed_terms_and_conditions' => true,
            'setup_updated_at' => now(),
        ], null, 'setup');

        $cacheKey = 'tenant_setup_status_'.tenant('id');
        Cache::tags([tenant_tag()])->forget($cacheKey);

        return redirect()->route('setup.regional');
    }

    public function regional()
    {
        $countries = $this->landlordService->getCountries();
        $zones_array = [];
        $timestamp = time();
        foreach (timezone_identifiers_list() as $key => $zone) {
            date_default_timezone_set($zone);
            $zones_array[$key]['zone'] = $zone;
            $zones_array[$key]['diff_from_GMT'] = 'UTC/GMT '.date('P', $timestamp);
        }
        $currencies = Currency::all();

        return view('setup.regional-settings', compact('countries', 'zones_array', 'currencies'));
    }

    public function storeRegional(Request $request)
    {
        $country = explode('-', $request->country);
        $country_id = $country[0];
        $country_name = $country[1];

        Setting::set([
            'country_id' => $country_id,
            'country_name' => $country_name,
            'timezone' => $request->timezone ?? 'Asia/Dhaka',
            'date_format' => $request->date_format ?? 'd-m-Y',
            'time_format' => $request->time_format ?? '12',
            'default_currency' => $request->currency,
            'thousand_separator' => $request->thousand_separator ?? ',',
            'decimal_digits' => $request->decimal_digits ?? 2,
            'currency_position' => $request->currency_position ?? 'left',
            'currency_display_type' => $request->currency_display_type ?? 'symbol',
        ], null, 'general');

        if ($request->has('language')) {
            $locale = $request->language;
            $locales = array_keys(config('locales'));

            if (in_array($locale, $locales)) {
                session(['locale' => $locale]);
            }
        }

        // নেক্সট স্টেপ হবে 'branch'
        Setting::set([
            'setup_step' => 'branch',
            'setup_updated_at' => now(),
        ], null, 'setup');

        $cacheKey = 'tenant_setup_status_'.tenant('id');
        Cache::tags([tenant_tag()])->forget($cacheKey);
        Cache::tags([tenant_tag()])->forget('general_settings_' . tenant('id'));

        return redirect()->route('setup.branch');
    }

    public function branch()
    {
        // অ্যাকাউন্ট সংক্রান্ত ফিল্টার বাদ দেওয়া হয়েছে
        $countryName = Setting::get('country_name');
        $divisions = $this->landlordService->getDivisions();

        return view('setup.branch-settings', compact('divisions', 'countryName'));
    }

    public function storeBranch(Request $request)
    {
        // default_acc ভ্যালিডেশন বাদ দেওয়া হয়েছে
        $request->validate([
            'branch_name' => 'required|string|max:200',
            'branch_code' => 'nullable|string|max:20',
            'bin_number' => 'nullable|string|max:50',
        ]);

        DB::beginTransaction();
        try {
            $data = $request->all();
            $general_settings = Setting::group('general');
            $data['address']['country'] = $general_settings['country_name'];

            $full_address = $this->landlordService->storeTenantAddress(tenant('id'), $data['address']);
            $slug = Branch::generateUniqueSlug($request->branch_name);

            $branch = Branch::create([
                'name' => $request->branch_name,
                'slug' => $slug,
                'branch_code' => $request->branch_code ?? null,
                'address' => $full_address,
                'phone' => $general_settings['company_phone'] ?? null,
                'email' => $general_settings['company_email'] ?? null,
                'default_acc' => null, // পরে Accounting সেটাপ হতে সেট হবে
                'currency_id' => $general_settings['default_currency'],
                'timezone' => $general_settings['timezone'],
                'bin_number' => $request->bin_number ?? null,
            ]);

            Setting::set([
                'default_branch' => $branch->id,
            ], null, 'general');

            // নেক্সট স্টেপ হবে 'accounting'
            Setting::set([
                'setup_step' => 'accounting',
                'setup_updated_at' => now(),
            ], null, 'setup');

            $cacheKey = 'tenant_setup_status_'.tenant('id');
            Cache::tags([tenant_tag()])->forget($cacheKey);
            Cache::tags([tenant_tag()])->forget('general_settings_' . tenant('id'));

            DB::commit();

            return redirect()
                ->route('setup.accounting')
                ->with('success', 'Branch Settings Updated Successfully!');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error setup branch: '.$e->getMessage());

            return back()
                ->withInput()
                ->with('error', 'Failed to complete branch setup. Please try again.');
        }
    }

    public function accounting()
    {
        $branches  = Cache::tags([tenant_tag()])->remember('all_active_branches_' . tenant('id'), 3600, fn() => Branch::active()->get());
        $currencies = Cache::tags([tenant_tag()])->remember('all_currencies_' . tenant('id'), 3600, function () {
            return Currency::select('id', 'name', 'code', 'symbol')->get();
        });

        $defaultCurrencyId = Setting::get('default_currency');
        return view('setup.accounting-settings', compact('branches', 'currencies', 'defaultCurrencyId'));
    }

    public function storeAccounting(Request $request, AccountingIntegrationService $accIntegration)
    {
        $data = $request->validate([
            'fiscal_start_from' => 'required|date_format:M-Y',
            'current_period' => 'required|integer|between:1,12',
            'fiscal_year_name' => 'required|string|max:20',
            'account_type' => 'required|in:cash,mobile,bank,other',
            'account_name' => 'required|string|max:150',

            'account_number' => 'required_if:account_type,bank|nullable|string|max:100',
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'opening_balance_date' => ['nullable', 'string'],
            'bank_name' => 'required_if:account_type,bank|nullable|string|max:150',
            'branch_name' => 'nullable|string|max:150',
            'routing_number' => 'nullable|string|max:100',
            'branch_id' => 'nullable|string|exists:branches,id',
        ]);

        DB::beginTransaction();
        try {
            $openingDate = $request->filled('opening_balance_date')
                ? Carbon::parse($request->opening_balance_date)->format('Y-m-d')
                : now()->toDateString();

            $data['opening_balance_date'] = $openingDate;
            $data['is_default'] = true;
            $data['currency_id'] = Setting::get('default_currency');

            $fiscalYear = $this->accountingService->createFiscalYear($data);
            $account = $this->accountingService->createLedgerAccount($data);
            $openingBalance = (float) ($data['opening_balance'] ?? 0);

            $accIntegration->syncAccountOpeningBalance($account, $openingBalance, $openingDate);

            // নতুন তৈরি হওয়া প্রাথমিক অ্যাকাউন্টটিকে সিস্টেমের Default Account হিসেবে সেটিং ও ব্রাঞ্চে সেট করা
            Setting::set([
                'default_acc' => $account->id,
            ], null, 'general');

            // $defaultBranchId = Setting::get('default_branch');
            // if ($defaultBranchId) {
            //     Branch::where('id', $defaultBranchId)->update([
            //         'default_acc' => $account->id,
            //     ]);
            // }

            // নেক্সট স্টেপ 'complete'
            Setting::set([
                'setup_step' => 'complete',
                'setup_updated_at' => now(),
            ], null, 'setup');

            $cacheKey = 'tenant_setup_status_'.tenant('id');
            Cache::tags([tenant_tag()])->forget($cacheKey);
            Cache::tags([tenant_tag()])->forget('general_settings_' . tenant('id'));

            DB::commit();

            return redirect()
                ->route('setup.complete')
                ->with('success', 'Accounting setup completed successfully.');
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Error setup accounting: '.$e->getMessage());

            return back()
                ->withInput()
                ->with('error', 'Failed to complete accounting setup. Please try again.');
        }
    }

    public function completeSetup()
    {
        return view('setup.complete-setup');
    }

    public function storeCompleteSetup(Request $request)
    {
        Setting::set([
            'is_setup_complete' => true,
            'setup_completed_at' => now()
        ], null, 'setup');
        
        $cacheKey = 'tenant_setup_status_'.tenant('id');
        Cache::tags([tenant_tag()])->forget($cacheKey);
        Cache::tags([tenant_tag()])->forget('general_settings_' . tenant('id'));

        return redirect()->route('dashboard')->with('success', 'Initial Setup Complete Successfully!');
    }

    public function getDivisions()
    {
        $divisions = $this->landlordService->getDivisions();

        return response()->json($divisions);
    }

    public function getDistricts($id)
    {
        $districts = $this->landlordService->getDistrictByDivId($id);

        return view('setup._address_options', ['items' => $districts, 'itemName' => 'District']);
    }

    public function getUpazillas($id)
    {
        $upazilas = $this->landlordService->getUpazilasByDisId($id);

        return view('setup._address_options', ['items' => $upazilas, 'itemName' => 'Upazilla']);
    }

    public function getUnions($id)
    {
        $unions = $this->landlordService->getUnionsByUpazillaId($id);

        return view('setup._address_options', ['items' => $unions, 'itemName' => 'Union']);
    }
}