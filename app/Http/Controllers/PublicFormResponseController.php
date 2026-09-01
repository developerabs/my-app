<?php

namespace App\Http\Controllers;

use App\DataTables\PublicFormResponseDataTable;
use App\Models\PublicForm;
use App\Models\PublicFormResponse;
use App\Traits\HasFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PublicFormResponseController extends Controller
{
    use HasFiles;

    public function index(PublicFormResponseDataTable $dataTable, $id)
    {
        request()->merge(['public_form_id' => $id]);

        $form = PublicForm::with('fields')->find($id);

        $filterableFields = collect($form?->fields ?? [])
            ->filter(fn ($field) => (bool) $field->filterable)
            ->map(function ($field) {
                $key = $field->name ?: Str::slug($field->label ?: 'field');
                $key = str_replace('-', '_', $key);

                return [
                    'key' => $key,
                    'elementId' => 'filter-field-' . str_replace('_', '-', $key),
                    'label' => $field->label ?: ucfirst($key),
                    'type' => $field->type,
                    'options' => (array) ($field->options ?? []),
                ];
            })
            ->values();

        return $dataTable->render('backend.public_forms_responses.index', compact('id', 'form', 'filterableFields'));
    }

    public function show($id)
    {
        $publicFormResponse = PublicFormResponse::with('publicForm.fields')->findOrFail($id);

        $fieldsByKey = collect($publicFormResponse->publicForm->fields ?? [])
            ->keyBy(fn ($field) => str_replace('-', '_', $field->name ?: Str::slug($field->label ?: 'field')));

        $fields = collect($publicFormResponse->response_data ?? [])
            ->map(function ($value, $key) use ($fieldsByKey) {
                $field = $fieldsByKey->get($key);
                $type = $field->type ?? 'text';
                $displayValue = is_array($value) ? implode(', ', $value) : $value;

                return [
                    'label' => $field->label ?? str_replace('_', ' ', $key),
                    'type' => $type,
                    'value' => $displayValue,
                    'url' => ($type === 'file' && $value) ? file_url($value) : null,
                ];
            })
            ->values();

        return response()->json([
            'id' => $publicFormResponse->id,
            'response_data' => $publicFormResponse->response_data,
            'fields' => $fields,
        ]);
    }

    public function destroy(PublicFormResponse $publicFormResponse)
    {
        try {
            $publicFormResponse->delete();
            return response()->json([
                'status' => true,
                'message' => __('file.message.public_form_response_deleted_successfully'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting Public Form Response: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => __('file.message.public_form_response_delete_failed'),
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        try {
            PublicFormResponse::whereIn('id', $ids)->delete();
            return response()->json([
                'status' => true,
                'message' => __('file.message.public_form_responses_deleted_successfully'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error bulk deleting Public Form Responses: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => __('file.message.public_form_responses_delete_failed'),
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
