<?php

namespace App\Http\Controllers;

use App\DataTables\CategoryDataTable;
use App\Models\Category;
use App\Models\CategoryType;
use App\Traits\HasFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    use HasFiles;

    /**
     * Display a listing of the categories.
     */
    public function index(CategoryDataTable $dataTable)
    {
        $categoryTypes = CategoryType::all(['id', 'display_name']);
        $categories = Category::all(['id', 'name']);

        // Fetch distinct sources for the filter dropdown
        $sources = Category::distinct()->pluck('source_from')->filter()->values();

        return $dataTable->render('backend.categories.index', compact('categoryTypes', 'categories', 'sources'));
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'             => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories')
                    ->where(function ($query) use ($request) {
                        return $query->where('category_type_id', $request->category_type_id)
                            ->whereNull('deleted_at');
                    })
            ],
            'category_type_id' => 'required|exists:category_types,id',
            'parent_id'        => 'nullable|exists:categories,id',
            'external_id'      => 'nullable|string|max:100',
            'source_from'      => 'nullable|string|max:50',
            'description'      => 'nullable|string',
            'sort_order'       => 'nullable|integer|min:0',
            'is_active'        => 'nullable',
            'is_featured'      => 'nullable',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'image'            => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                // Generate unique slug
                $slug = Category::generateUniqueSlug($request->name);

                // Handle image upload with thumbnail via Trait
                $imagePath = null;
                if ($request->hasFile('image')) {
                    $imagePath = $this->processImage($request->file('image'), 'categories', [
                        'width'       => 500,
                        'thumbnail'   => true,
                        'thumb_width' => 150,
                    ]);
                }

                // Note: 'id' (UUID), 'created_by' are auto-handled by Model's booted() method
                $category = Category::create([
                    'parent_id'        => $request->parent_id,
                    'category_type_id' => $request->category_type_id,
                    'external_id'      => $request->external_id,
                    'source_from'      => $request->source_from ?? 'web',
                    'name'             => $request->name,
                    'slug'             => $slug,
                    'description'      => $request->description,
                    'image'            => $imagePath,
                    'sort_order'       => $request->sort_order ?? 0,
                    'is_active'        => $request->boolean('is_active', true),
                    'is_featured'      => $request->boolean('is_featured', false),
                    'meta_title'       => $request->meta_title ?? $request->name,
                    'meta_description' => $request->meta_description,
                ]);

                return response()->json([
                    'status'  => true,
                    'id'      => $category->id,
                    'message' => __('file.message.category_created')
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Category Creation Failed: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => __('file.message.category_create_failed'),
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get categories by type for dynamic dropdowns.
     */
    public function getCategoriesByType($type)
    {
        $categories = Category::where('category_type_id', $type)->select('id', 'name')->get();
        return response()->json($categories);
    }

    public function getCategoriesByStatusType($type)
    {
        $categoryType = CategoryType::with('categories')->where('name', $type)->first();
        $categories = $categoryType ? $categoryType->categories()->active()->select('id', 'name')->get() : collect();

        return response()->json([
            'status' => true,
            'categories' => $categories,
        ]);
    }

    /**
     * Show the form for editing the specified category.
     */
    public function edit(Category $category)
    {
        // Category image_url is automatically provided by our HasFiles Trait's magic accessor
        return response()->json([
            'category'  => $category,
            'image_url' => $category->image_url,
            'thumb_url' => $category->thumb_url
        ]);
    }

    /**
     * Update the specified category in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name'             => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories')
                    ->where(function ($query) use ($request) {
                        return $query->where('category_type_id', $request->category_type_id)
                            ->whereNull('deleted_at');
                    })
                    ->ignore($id, 'id')
            ],
            'category_type_id' => 'required|exists:category_types,id',
            'parent_id'        => 'nullable|exists:categories,id',
            'external_id'      => 'nullable|string|max:100',
            'source_from'      => 'nullable|string|max:50',
            'description'      => 'nullable|string',
            'sort_order'       => 'nullable|integer|min:0',
            'is_active'        => 'nullable',
            'is_featured'      => 'nullable',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'image'            => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        try {
            return DB::transaction(function () use ($request, $id) {
                $category = Category::findOrFail($id);

                $slug = Category::generateUniqueSlug($request->name, $category->id);

                // Image Handle: This automatically deletes old files & clears cache via Trait
                $imagePath = $category->image;
                if ($request->hasFile('image')) {
                    $imagePath = $this->processImage($request->file('image'), 'categories', [
                        'width'       => 600,
                        'thumbnail'   => true,
                        'thumb_width' => 150
                    ], $category->image);
                }

                $category->update([
                    'parent_id'        => $request->parent_id,
                    'category_type_id' => $request->category_type_id,
                    'name'             => $request->name,
                    'slug'             => $slug,
                    'description'      => $request->description,
                    'image'            => $imagePath,
                    'sort_order'       => $request->sort_order ?? 0,
                    'is_active'        => $request->boolean('is_active', true),
                    'is_featured'      => $request->boolean('is_featured', false),
                    'meta_title'       => $request->meta_title ?? $request->name,
                    'meta_description' => $request->meta_description,
                ]);

                return response()->json([
                    'status'  => true,
                    'message' => __('file.message.category_updated')
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Category Update Failed: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => __('file.message.category_update_failed')
            ], 500);
        }
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy(Category $category)
    {
        if ($category->children()->exists()) {
            return response()->json([
                'status'  => false,
                'message' => __('messages.category_has_children', ['name' => $category->name])
            ], 422);
        }

        try {
            // Model's booted() method will handle auditing and cache clearing
            $category->delete();
            return response()->json([
                'status'  => true,
                'message' => __('messages.category_deleted_success', ['name' => $category->name])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => __('messages.generic_error')
            ], 500);
        }
    }

    /**
     * Bulk delete categories.
     */
    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;

        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No categories selected.'], 400);
        }

        $categoriesWithChildren = Category::whereIn('id', $ids)->whereHas('children')->pluck('name')->toArray();

        if (!empty($categoriesWithChildren)) {
            return response()->json([
                'success' => false,
                'message' => __('file.message.bulk_delete_restricted', ['names' => implode(', ', $categoriesWithChildren)])
            ], 422);
        }

        try {
            // Using each->delete() ensures model events (like deleting file cache) are fired
            Category::whereIn('id', $ids)->chunkById(100, function ($categories) {
                foreach ($categories as $category) {
                    $category->delete();
                }
            });
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Something went wrong!'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => count($ids) . ' categories deleted successfully.',
        ]);
    }
}
