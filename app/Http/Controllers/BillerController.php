<?php

namespace App\Http\Controllers;

use App\DataTables\BillerDataTable;
use App\Models\Biller;
use App\Rules\UniqueWithTrashCheck;
use App\Traits\HasFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillerController extends Controller
{
    use HasFiles;
    public function index(BillerDataTable $billerDataTable)
    {
        return $billerDataTable->render('backend.billers.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255', new UniqueWithTrashCheck(Biller::class, 'name')],
            'company_name'   => 'nullable|string|max:255',
            'propiter_name'  => 'nullable|string|max:255',
            'email'          => 'nullable|email|max:255',
            'phone'          => 'required|string|max:20',
            'address'        => 'nullable|string',
            'bin'            => 'nullable|string|max:100',
            'website_url'    => 'nullable|url',
            'tnc'            => 'nullable|string',
            'meta'           => 'nullable|string',
            'is_active'      => 'nullable|boolean',
            'logo'           => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'certificate'    => 'nullable|mimes:pdf,jpeg,png,jpg|max:5120', // Max 5MB
        ]);

        try {
            return DB::transaction(function () use (&$validated, $request) {
                if ($request->hasFile('logo')) {
                    $validated['logo'] = $this->processImage($request->file('logo'), 'billers', [
                        'width' => 500,
                    ]);
                }
                if ($request->hasFile('certificate')) {
                    $validated['certificate'] = $this->uploadUploadedFile(
                        $request->file('certificate'),
                        'billers/certificates',
                    );
                }
                $biller = Biller::create($validated);
                return response()->json(['status' => true, 'message' => 'Biller created successfully!', 'biller' => $biller]);
            });
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Failed to create biller.', 'error' => $e->getMessage()], 500);
        }
    }

    public function edit(Biller $biller)
    {
        return response()->json([
            'success' => true,
            'biller' => $biller,
            'logo_url' => $biller->logo_url,
            'certificate_url' => $biller->certificate_url
        ], 200);
    }

    public function update(Request $request, Biller $biller)
    {
        // ১. ভ্যালিডেশন (ইউনিক চেক করার সময় বর্তমান বিলারের আইডি বাদ দেওয়া হয়েছে)
        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255', new UniqueWithTrashCheck(Biller::class, 'name', $biller->id)],
            'company_name'   => 'nullable|string|max:255',
            'propiter_name'  => 'nullable|string|max:255',
            'email'          => 'nullable|email|max:255',
            'phone'          => 'required|string|max:20',
            'address'        => 'nullable|string',
            'bin'            => 'nullable|string|max:100',
            'website_url'    => 'nullable|url',
            'tnc'            => 'nullable|string',
            'meta'           => 'nullable|string',
            'is_active'      => 'nullable|boolean',
            'logo'           => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'certificate'    => 'nullable|mimes:pdf,jpeg,png,jpg|max:5120',
        ]);

        try {
            return DB::transaction(function () use (&$validated, $request, $biller) {

                // ২. লোগো আপডেট (পুরানো লোগো ডিলিট করে নতুনটি প্রসেস করবে)
                if ($request->hasFile('logo')) {
                    // processImage মেথডটি নিজেই পুরনো ফাইল ডিলিট করার ক্ষমতা রাখে যদি oldPath দেওয়া হয়
                    $validated['logo'] = $this->processImage(
                        $request->file('logo'),
                        'billers',
                        ['width' => 500],
                        $biller->getRawOriginal('logo') // পুরনো পাথ
                    );
                }

                // ৩. সার্টিফিকেট আপডেট (PDF/Image)
                if ($request->hasFile('certificate')) {
                    $validated['certificate'] = $this->updateFile(
                        $request,
                        'certificate',
                        $biller->getRawOriginal('certificate'),
                        'billers/certificates',
                    );
                }

                // ৪. স্ট্যাটাস হ্যান্ডলিং
                $validated['is_active'] = $request->has('is_active') ? 1 : 0;

                // ৫. ডাটা আপডেট
                $biller->update($validated);

                return response()->json([
                    'status'  => true,
                    'message' => 'Biller updated successfully!',
                    'biller'  => $biller
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to update biller.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Biller $biller)
    {
        try {
            $biller->delete();
            return response()->json([
                'status'  => true,
                'message' => 'Biller deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to delete biller.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
