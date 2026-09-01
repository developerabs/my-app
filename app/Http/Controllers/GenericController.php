<?php

namespace App\Http\Controllers;

use App\DataTables\GenericDataTable;
use App\Models\Generic;
use App\Rules\UniqueWithTrashCheck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenericController extends Controller
{
    public function index(GenericDataTable $dataTable)
    {
        return $dataTable->render('backend.generics.generics');
    }

    public function store(Request $request)
    {
        // Always write code comments in English.

        // English Comment: Validate the incoming request parameters with clean data rules
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                new UniqueWithTrashCheck(Generic::class, 'name'),
            ],
            'description' => 'nullable|string',
            'sort_order'  => 'nullable|integer|min:0',
            'is_active'   => 'nullable',
            'is_featured' => 'nullable',
            'source_from' => 'nullable|string|max:50', // English Comment: Always validate input data fields
        ]);

        try {
            // English Comment: Execute clean atomic database transaction keeping HTTP responses outside
            $generic = DB::transaction(function () use ($request) {
                $slug = Generic::generateUniqueSlug($request->name);
                return Generic::create([
                    'name'         => $request->name,
                    'slug'         => $slug,
                    'description'  => $request->description,
                    'sort_order'   => $request->sort_order ?? 0,
                    'is_active'    => $request->boolean('is_active', true),
                    'is_featured'  => $request->boolean('is_featured', false),
                    'source_from'  => $request->source_from ?? 'web',
                ]);
            });

            // English Comment: Return standard JSON API response after successful transaction commit
            return response()->json([
                'status'  => true,
                'message' => __('file.message.generic_created'),
                'id'      => $generic->id,
            ], 201); // English Comment: Using explicit 201 HTTP status code for resource creation

        } catch (\Exception $e) {
            Log::error('Generic Creation Failed: ' . $e->getMessage(), [
                'input' => $request->except(['_token'])
            ]);

            return response()->json([
                'status'  => false,
                'message' => __('file.message.generic_create_failed'),
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function edit(Generic $generic)
    {
        return response()->json([
            'generic' => $generic,
        ]);
    }

    public function update(Request $request, Generic $generic)
    {
        // English Comment: Validate the incoming request parameters with clean data rules
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                new UniqueWithTrashCheck(Generic::class, 'name', $generic->id),
            ],
            'description' => 'nullable|string',
            'sort_order'  => 'nullable|integer|min:0',
            'is_active'   => 'nullable',
            'is_featured' => 'nullable',
            'source_from' => 'nullable|string|max:50', // English Comment: Always validate input data fields
        ]);

        try {
            // English Comment: Execute clean atomic database transaction keeping HTTP responses outside
            DB::transaction(function () use ($request, $generic) {
                $slug = Generic::generateUniqueSlug($request->name, $generic->id);
                $generic->update([
                    'name'         => $request->name,
                    'slug'         => $slug,
                    'description'  => $request->description,
                    'sort_order'   => $request->sort_order ?? 0,
                    'is_active'    => $request->boolean('is_active', true),
                    'is_featured'  => $request->boolean('is_featured', false),
                    'source_from'  => $request->source_from ?? 'web',
                ]);
            });

            // English Comment: Return standard JSON API response after successful transaction commit
            return response()->json([
                'status'  => true,
                'message' => __('file.message.generic_updated'),
            ]);

        } catch (\Exception $e) {
            Log::error('Generic Update Failed: ' . $e->getMessage(), [
                'input' => $request->except(['_token'])
            ]);

            return response()->json([
                'status'  => false,
                'message' => __('file.message.generic_update_failed'),
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Generic $generic)
    {
        try {
            $generic->delete();

            return response()->json([
                'status'  => true,
                'message' => __('file.message.generic_deleted'),
            ]);
        } catch (\Exception $e) {
            Log::error('Generic Deletion Failed: ' . $e->getMessage(), [
                'generic_id' => $generic->id
            ]);

            return response()->json([
                'status'  => false,
                'message' => __('file.message.generic_delete_failed'),
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'uuid|exists:generics,id',
        ]);

        try {
            DB::transaction(function () use ($request) {
                Generic::whereIn('id', $request->ids)->get()->each->delete();
            });

            return response()->json([
                'status'  => true,
                'message' => __('file.message.generics_deleted'),
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk Deletion of Generics Failed: ' . $e->getMessage(), [
                'ids' => $request->ids
            ]);

            return response()->json([
                'status'  => false,
                'message' => __('file.message.generics_delete_failed'),
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    
}
