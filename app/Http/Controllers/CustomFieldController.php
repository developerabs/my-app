<?php

namespace App\Http\Controllers;

use App\DataTables\CustomFieldDataTable;
use App\Models\CustomField;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CustomFieldController extends Controller
{
    public function index(CustomFieldDataTable $dataTable)
    {
        return $dataTable->render('backend.custom_fields.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'model_type'   => ['required', 'string', Rule::in(array_values(config('sherazipos.model_mappings')))],
            'label'        => 'required|string|max:191',
            'type'         => 'required|in:text,number,date,email,textarea,select,radio,checkbox',
            'options'      => 'required_if:type,select,radio,checkbox|nullable|string',
            'default_value' => 'nullable|string|max:191',
            'placeholder'  => 'nullable|string|max:191',
            'is_required'  => 'nullable|boolean',
            'order'        => 'nullable|integer|min:0',
            'show_in_list' => 'nullable|boolean',
            'is_active'    => 'nullable|boolean',
        ]);

        try {
            $name = Str::slug($request->label, '_');

            $exists = CustomField::where('model_type', $request->model_type)
                ->where('name', $name)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => __('file.error.field_already_exists_for_this_model')
                ], 422);
            }

            $options = null;
            if ($request->options) {
                $options = array_values(array_filter(array_map('trim', explode(',', $request->options))));
            }
            CustomField::create([
                'model_type'   => $request->model_type,
                'label'        => $request->label,
                'name'         => $name,
                'type'         => $request->type,
                'options'      => $options,
                'default_value' => $request->default_value,
                'placeholder'  => $request->placeholder,
                'is_required'  => $request->is_required ?? false,
                'order'        => $request->order ?? 0,
                'show_in_list' => $request->show_in_list ?? false,
                'is_active'    => $request->is_active ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => __('file.message.custom_field_created_successfully')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('file.error.something_went_wrong') . ': ' . $e->getMessage()
            ], 500);
        }
    }

    public function edit(CustomField $customField)
    {
        try {
            /* English Comment: 
           If the options are stored as an array/JSON, we return them 
           joined by commas for the textarea in the form.
        */
            $data = $customField->toArray();

            if ($customField->options && is_array($customField->options)) {
                $data['options_string'] = implode(', ', $customField->options);
            } else {
                $data['options_string'] = '';
            }

            return response()->json([
                'success' => true,
                'data'    => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('file.error.something_went_wrong')
            ], 500);
        }
    }

    public function update(Request $request, CustomField $customField)
    {
        $request->validate([
            'model_type'   => ['required', 'string', Rule::in(array_values(config('sherazipos.model_mappings')))],
            'label'        => 'required|string|max:191',
            'type'         => 'required|in:text,number,date,email,textarea,select,radio,checkbox',
            'options'      => 'required_if:type,select,radio,checkbox|nullable|string',
            'default_value' => 'nullable|string|max:191',
            'placeholder'  => 'nullable|string|max:191',
            'is_required'  => 'nullable|boolean',
            'order'        => 'nullable|integer|min:0',
            'show_in_list' => 'nullable|boolean',
            'is_active'    => 'nullable|boolean',
        ]);

        try {
            $exists = CustomField::where('model_type', $request->model_type)
                ->where('name', $customField->name) // যেহেতু নাম সাধারণত চেঞ্জ হয় না
                ->where('id', '!=', $customField->id)
                ->exists();

            if ($exists) {
                return response()->json(['success' => false, 'message' => __('file.error.field_already_exists')], 422);
            }
            $options = null;
            if (in_array($request->type, ['select', 'radio', 'checkbox']) && $request->options) {
                $options = array_values(array_filter(array_map('trim', explode(',', $request->options))));
            }

            $customField->update([
                'model_type'   => $request->model_type,
                'label'        => $request->label,
                // 'name' => Str::slug($request->label, '_'), // সাধারণত এডিটে 'name' পরিবর্তন না করাই নিরাপদ
                'type'         => $request->type,
                'options'      => $options,
                'default_value' => $request->default_value,
                'placeholder'  => $request->placeholder,
                'is_required'  => $request->is_required ?? false,
                'order'        => $request->order ?? 0,
                'show_in_list' => $request->show_in_list ?? false,
                'is_active'    => $request->is_active ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => __('file.message.custom_field_updated_successfully')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('file.error.something_went_wrong') . ': ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(CustomField $customField)
    {
        try {
            $customField->delete();

            return response()->json([
                'success' => true,
                'message' => __('file.message.custom_field_deleted_successfully')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('file.error.something_went_wrong') . ': ' . $e->getMessage()
            ], 500);
        }
    }
}
