<?php

namespace App\Http\Controllers;

use App\DataTables\BrandDataTable;
use App\Models\Brand;
use App\Rules\UniqueWithTrashCheck;
use App\Traits\HasFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BrandController extends Controller
{
    use HasFiles;
    public function index(BrandDataTable $dataTable)
    {
        $sources = Brand::distinct()->pluck('source_from')->filter()->values();
        return $dataTable->render('backend.brands.brands', compact('sources'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'             => [
                'required',
                'string',
                'max:255',
                new UniqueWithTrashCheck(Brand::class, 'name'),
            ],
            'website_url'      => 'nullable|url:http,https|max:255',
            'description'      => 'nullable|string',
            'brand_logo'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'cover_image'      => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'sort_order'       => 'nullable|integer|min:0',
            'is_active'        => 'nullable',
            'is_featured'      => 'nullable',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords'    => 'nullable|string|max:255',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $slug = Brand::generateUniqueSlug($request->name);

                $brand_logo = null;
                if ($request->hasFile('brand_logo')) {
                    $brand_logo = $this->processImage($request->file('brand_logo'), 'brands', [
                        'width' => 500,
                        'quality' => 80,
                        'thumbnail' => true,
                        'thumb_width' => 150,
                    ]);
                }

                $cover_image = null;
                if ($request->hasFile('cover_image')) {
                    $cover_image = $this->processImage($request->file('cover_image'), 'brands', [
                        'width' => 1200,
                        'quality' => 80,
                    ]);
                }

                $brand = Brand::create([
                    'name'             => $request->name,
                    'slug'             => $slug,
                    'website_url'      => $request->website_url,
                    'description'      => $request->description,
                    'brand_logo'       => $brand_logo,
                    'cover_image'      => $cover_image,
                    'sort_order'       => $request->sort_order ?? 0,
                    'is_active'        => $request->boolean('is_active', true),
                    'is_featured'      => $request->boolean('is_featured', false),
                    'meta_title'       => $request->meta_title ?? $request->name,
                    'meta_description' => $request->meta_description,
                    'meta_keywords'    => $request->meta_keywords,
                    'source_from'      => $request->source_from ?? 'web',
                ]);

                return response()->json([
                    'status'  => true,
                    'message' => __('file.message.brand_created'),
                    'id'      => $brand->id,
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Brand Creation Failed: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => __('file.message.brand_create_failed'),
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function edit(Brand $brand)
    {

        return response()->json([
            'brand' => $brand,
            'brand_logo' => $brand->brand_logo_url,
            'cover_image' => $brand->cover_image_url
        ]);
    }

    public function update(Request $request, Brand $brand)
    {
        // ১. ভ্যালিডেশন (ইউনিক চেক করার সময় বর্তমান ব্র্যান্ডের আইডি বাদ দেওয়া হয়েছে)
        $request->validate([
            'name'             => [
                'required',
                'string',
                'max:255',
                // English: Check unique name but ignore the current brand ID
                new UniqueWithTrashCheck(Brand::class, 'name', $brand->id),
            ],
            'website_url'      => 'nullable|url:http,https|max:255',
            'description'      => 'nullable|string',
            'brand_logo'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'cover_image'      => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'sort_order'       => 'nullable|integer|min:0',
            'is_active'        => 'nullable',
            'is_featured'      => 'nullable',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords'    => 'nullable|string|max:255',
        ]);

        try {
            return DB::transaction(function () use ($request, $brand) {
                $slug = Brand::generateUniqueSlug($request->name, $brand->id);

                // Update logo Image
                if ($request->hasFile('brand_logo')) {
                    $brand->brand_logo = $this->processImage(
                        $request->file('brand_logo'),
                        'brands',
                        [
                            'width'       => 500,
                            'quality'     => 80,
                            'thumbnail'   => true,
                            'thumb_width' => 150,
                        ],
                        $brand->brand_logo //Delete old logos
                    );
                }

                // Update Cover Image
                if ($request->hasFile('cover_image')) {
                    $brand->cover_image = $this->processImage(
                        $request->file('cover_image'),
                        'brands',
                        [
                            'width'   => 1200,
                            'quality' => 80,
                        ],
                        $brand->cover_image //Delete old image
                    );
                }

                // Others Update
                $brand->update([
                    'name'             => $request->name,
                    'slug'             => $slug,
                    'website_url'      => $request->website_url,
                    'description'      => $request->description,
                    'brand_logo'       => $brand->brand_logo,
                    'cover_image'      => $brand->cover_image,
                    'sort_order'       => $request->sort_order ?? 0,
                    'is_active'        => $request->boolean('is_active', $brand->is_active),
                    'is_featured'      => $request->boolean('is_featured', $brand->is_featured),
                    'meta_title'       => $request->meta_title ?? $request->name,
                    'meta_description' => $request->meta_description,
                    'meta_keywords'    => $request->meta_keywords,
                ]);

                return response()->json([
                    'status'  => true,
                    'message' => __('file.message.brand_updated'),
                    'data'    => $brand
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Brand Update Failed: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => __('file.message.brand_update_failed'),
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Brand $brand)
    {
        try {
            $brand->delete();
            return response()->json([
                'status'  => true,
                'message' => __('file.message.brand_deleted'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => __('file.message.brand_delete_failed'),
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;

        if (empty($ids)) {
            return response()->json(['status' => false, 'message' => 'No brands selected.'], 400);
        }

        try{
            // Using each->delete() ensures model events (like deleting file cache) are fired
            Brand::whereIn('id', $ids)->get()->each->delete();

            return response()->json([
                'status'  => true,
                'message' => count($ids) . ' brands deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Something went wrong!'], 500);
        }
    }
}
