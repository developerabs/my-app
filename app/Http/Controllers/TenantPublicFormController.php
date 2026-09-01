<?php

namespace App\Http\Controllers;

use App\DataTables\PublicFormDataTable;
use App\Http\Controllers\Controller;
use App\Models\CategoryType;
use App\Models\LeadSource;
use App\Models\LeadSubject;
use App\Models\PublicForm;
use App\Models\PublicFormField;
use App\Models\PublicFormToken;
use App\Models\Status;
use App\Models\User;
use App\Traits\HasFiles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantPublicFormController extends Controller
{
    use HasFiles;

    public function index(PublicFormDataTable $dataTable)
    {
        return $dataTable->render('backend.public_forms.index');
    }

    public function create(): View
    {
        $fieldTypes = ['text', 'email', 'number', 'textarea', 'select', 'file', 'date'];
        $data = $this->getCommonFormData('lead');
        
        return view('backend.public_forms.create', compact('fieldTypes', 'data'));
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        $validated['slug'] = $this->uniqueSlug($validated['title']);

        // check submitted for and submission mode
        if ($validated['submitted_for'] != 'lead' && $validated['submission_mode'] == 'auto_lead') {
            return response()->json([
                'success'  => false,
                'message'  => __('file.message.invalid_submission_mode_for_lead'),
            ], 400);
        }
        $uploadedLogo = null;
        if ($request->hasFile('custom_logo')) {
            $uploadedLogo = $this->processImage($request->file('custom_logo'), 'public-forms');
            $validated['custom_logo'] = $uploadedLogo;
        }
        $validated['default_category_id'] = $validated['category_id'] ?? null;

        try {
            $publicForm = DB::transaction(function () use ($request, $validated) {
                $form = PublicForm::create($validated);

                $this->syncFields($form, $request);
                $this->createToken($form, 43200);

                return $form;
            });

            if ($request->ajax()) {
                return response()->json([
                    'success'  => true,
                    'message'  => __('file.message.public_form_created_successfully'),
                    'redirect' => route('public-forms.index'),
                ], 201);
            }
            return redirect()->route('public-forms.index')->with('success', __('file.message.public_form_created_successfully'));
            
        } catch (\Exception $e) {
            if ($uploadedLogo) {
                $this->deleteFile($uploadedLogo);
            }

            if ($request->ajax()) {
                return response()->json([
                    'success'  => false,
                    'message'  => __('file.message.failed_to_create_public_form') . ': ' . $e->getMessage(),
                ], 500);
            }
            return redirect()->back()->withInput()->with('error', __('file.message.failed_to_create_public_form') . ': ' . $e->getMessage());
        }
    }

    public function edit(PublicForm $publicForm): View
    {
        // ডাটাবেজ থেকে রিলেটেড ফিল্ডগুলো ফেচ করে একটি প্রপার ফরম্যাটে সাজিয়ে নিচ্ছি
        $savedFields = $publicForm->fields()->orderBy('sort_order')->get()->map(function ($field) {
            return [
                'db_id'              => $field->id,
                'name'               => $field->name,
                'label'              => $field->label,
                'type'               => $field->type,
                'placeholder'        => $field->placeholder,
                'options'            => is_array($field->options) ? implode(', ', $field->options) : $field->options,
                'is_required'        => (bool) $field->is_default_required,
                'show_in_table'      => (bool) $field->show_in_table,
                'searchable'         => (bool) $field->searchable,
                'filterable'         => (bool) $field->filterable,
                'column_width'       => $field->column_width,
                'is_system_defined'  => (bool) $field->is_system_defined,
            ];
        });

        $fieldTypes = ['text', 'email', 'number', 'textarea', 'select', 'file', 'date'];
        $data = $this->getCommonFormData('lead');

        $systemDefinedFields = $this->getFieldsByType(new \Illuminate\Http\Request(['type' => 'lead']));

        // JsonResponse থেকে 'fields' অ্যারেটি বের করে নেওয়া
        if ($systemDefinedFields instanceof \Illuminate\Http\JsonResponse) {
            $responseData = $systemDefinedFields->getData();
            $systemDefinedFields = $responseData->fields ?? [];
        }

        return view('backend.public_forms.edit', compact('publicForm', 'savedFields', 'fieldTypes', 'data', 'systemDefinedFields'));
    }

    public function update(Request $request, PublicForm $publicForm)
    {
        $validated = $this->validated($request, $publicForm->id);

        $newLogo = null;
        
        if ($request->hasFile('custom_logo')) {
            $newLogo = $this->processImage($request->file('custom_logo'), 'public-forms', [], $publicForm->custom_logo);
            $validated['custom_logo'] = $newLogo;
        }
        $validated['default_category_id'] = $validated['category_id'] ?? null;

        try {
            DB::transaction(function () use ($publicForm, $request, $validated) {
                $publicForm->update($validated);
                $this->syncFields($publicForm, $request);
            });

            if ($request->ajax()) {
                return response()->json([
                    'success'  => true,
                    'message'  => __('file.message.public_form_updated_successfully'),
                    'redirect' => route('public-forms.index'),
                ], 200);
            }
            return redirect()->route('public-forms.index')->with('success', __('file.message.public_form_updated_successfully'));
            
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success'  => false,
                    'message'  => __('file.message.failed_to_update_public_form') . ': ' . $e->getMessage(),
                ], 500);
            }
            return redirect()->back()->withInput()->with('error', __('file.message.failed_to_update_public_form') . ': ' . $e->getMessage());
        }
    }

    public function destroy(PublicForm $publicForm)
    {
        try {
            $publicForm->delete();
            return response()->json([
                'status' => true,
                'message' => __('file.message.public_form_deleted_successfully'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting Public Form: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => __('file.message.public_form_delete_failed'),
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        try {
            PublicForm::whereIn('id', $ids)->delete();
            return response()->json([
                'status' => true,
                'message' => __('file.message.public_forms_deleted_successfully'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error bulk deleting Public Forms: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => __('file.message.public_forms_delete_failed'),
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function toggle(Request $request, PublicForm $publicForm)
    {
        $publicForm->update(['is_active' => !$publicForm->is_active, 'updated_by' => auth()->id()]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('file.message.public_form_status_updated_successfully'),
                'is_active' => $publicForm->is_active,
            ]);
        }

        return back()->with('success', __('file.message.public_form_status_updated_successfully'));
    }

    public function generateTokenizedLink(Request $request, $id): JsonResponse
    {
        $publicForm = PublicForm::findOrFail($id);
        abort_unless($publicForm->is_active, 422, 'Activate the form before generating a link.');
        $duration = $request->validate(['duration' => ['required', Rule::in(['24_hours', '7_days', '30_days'])]])['duration'];
        $minutes = ['24_hours' => 1440, '7_days' => 10080, '30_days' => 43200][$duration];
        $token = $this->createToken($publicForm, $minutes);

        return response()->json(['url' => $token['url'], 'expires_at' => $token['expires_at']], 201);
    }

    private function createToken(PublicForm $publicForm, int $minutes): array
    {
        $rawToken = Str::random(64);
        $expiresAt = now()->addMinutes($minutes);

        PublicFormToken::create([
            'public_form_id' => $publicForm->id,
            'token_hash' => hash('sha256', $rawToken),
            'token_encrypted' => encrypt($rawToken),
            'expires_at' => $expiresAt,
        ]);

        return [
            'url' => route('public-forms.show', [$publicForm->slug, $rawToken]),
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    private function syncFields(PublicForm $publicForm, Request $request): void
    {
        $request->validate([
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.db_id' => ['nullable', 'integer'],
            'fields.*.name' => ['required', 'string', 'max:80'],
            'fields.*.label' => ['required', 'string', 'max:255'],
            'fields.*.type' => ['required', Rule::in(['text', 'email', 'number', 'textarea', 'select', 'file', 'date'])],
            'fields.*.placeholder' => ['nullable', 'string', 'max:255'],
            'fields.*.is_required' => ['nullable', 'boolean'],
            'fields.*.is_system_defined' => ['nullable', 'boolean'],
            'fields.*.show_in_table' => ['nullable', 'boolean'], 
            'fields.*.searchable' => ['nullable', 'boolean'],    
            'fields.*.filterable' => ['nullable', 'boolean'],
            'fields.*.options' => ['nullable', 'string', 'max:5000'],
            'fields.*.column_width' => ['nullable', 'integer', 'min:1', 'max:3'],
        ]);

        $submittedFields = $request->input('fields', []);
        
        if ($request->input('submission_mode') === 'auto_lead' && !collect($submittedFields)->contains('name', 'name')) {
            abort(422, 'Add a field named "name" when automatic lead creation is enabled.');
        }

        $seenNames = [];
        $keptIds = [];

        foreach ($submittedFields as $index => $field) {
            $name = $field['name'];
            if (isset($seenNames[$name])) {
                abort(422, 'Each form field can only be added once.');
            }
            $seenNames[$name] = true;

            $isSystemDefined = !empty($field['is_system_defined']);
            $fieldType = $field['type'];
            $isRequired = !empty($field['is_required']);

            if ($isSystemDefined) {
                if ($name === 'name') {
                    $fieldType = 'text';
                    $isRequired = true;
                } elseif ($name === 'phone') {
                    $fieldType = 'text';
                    $isRequired = true;
                } 
            }

            $options = collect(explode(',', (string) ($field['options'] ?? '')))
                ->map(fn ($option) => trim($option))
                ->filter()
                ->unique()
                ->values()
                ->all();

            $attributes = [
                'public_form_id' => $publicForm->id,
                'name' => Str::slug($name, '_'),
                'label' => trim($field['label']),
                'type' => $fieldType,
                'placeholder' => trim((string) ($field['placeholder'] ?? '')),
                'is_default_required' => $isRequired,
                'is_system_defined' => $isSystemDefined,
                'show_in_table' => !empty($field['show_in_table']), // যুক্ত করা হয়েছে
                'searchable' => !empty($field['searchable']),       // যুক্ত করা হয়েছে
                'filterable' => !empty($field['filterable']),       // যুক্ত করা হয়েছে
                'options' => $fieldType === 'select' ? $options : [],
                'column_width' => (int) ($field['column_width'] ?? 1),
                'sort_order' => $index,
                'is_active' => true,
            ];

            $existing = !empty($field['db_id'])
                ? $publicForm->fields()->find($field['db_id'])
                : null;

            if ($existing) {
                $existing->update($attributes);
                $keptIds[] = $existing->id;
            } else {
                $newField = PublicFormField::create($attributes);
                $keptIds[] = $newField->id;
            }
        }

        $publicForm->fields()->whereNotIn('id', $keptIds)->delete();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:1000'],
            'submit_button_text' => ['required', 'string', 'max:100'],
            'submission_mode' => ['required'],
            'is_active' => ['nullable', 'boolean'],
            'logo_type' => ['nullable', Rule::in(['site_logo', 'custom'])],
            'custom_logo' => ['nullable', 'image', 'max:5120'],
            'submitted_for' => ['required'],
            'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
            'default_status_id' => ['nullable', 'exists:statuses,id'],
            'default_subject_id' => ['nullable', 'exists:lead_subjects,id'],
            'default_source_id' => ['nullable', 'exists:lead_sources,id'],
            'default_manager_id' => ['nullable', 'exists:users,id'],
            'default_assigned_to_id' => ['nullable', 'exists:users,id'],
            'fields' => ['nullable', 'array'],
            'fields.*.db_id' => ['nullable', 'exists:public_form_fields,id'],
            'fields.*.label' => ['required', 'string', 'max:255'],
            'fields.*.type' => ['required', 'string', 'max:50'],
            'fields.*.placeholder' => ['nullable', 'string', 'max:255'],
            'fields.*.is_default_required' => ['nullable', 'boolean'],
            'fields.*.show_in_table' => ['nullable', 'boolean'],
            'fields.*.searchable' => ['nullable', 'boolean'],
            'fields.*.filterable' => ['nullable', 'boolean'],
            'fields.*.options' => ['nullable', 'string'],
            'fields.*.column_width' => ['nullable', 'integer', 'min:1', 'max:12'],
            'fields.*.sort_order' => ['nullable', 'integer'],
            'fields.*.is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'public-form';
        $slug = $base;
        $counter = 2;
        while (PublicForm::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter++;
        }
        return $slug;
    }

    public function getFieldsByType(Request $request)
    {
        $submittedFor = $request->get('submitted_for', 'lead');

        if ($submittedFor === 'lead') {
            $fields = [
                [
                    'db_id' => '',
                    'name' => 'name',
                    'label' => 'Full Name',
                    'type' => 'text',
                    'placeholder' => 'Enter your full name',
                    'options' => '',
                    'is_required' => true,
                    'show_in_table' => true,
                    'searchable' => true,
                    'filterable' => false,
                    'column_width' => 1,
                    'is_system_defined' => true,
                ],
                [
                    'db_id' => '',
                    'name' => 'phone',
                    'label' => 'Phone Number',
                    'type' => 'text',
                    'placeholder' => 'Enter phone number',
                    'options' => '',
                    'is_required' => true,
                    'show_in_table' => true,
                    'searchable' => true,
                    'filterable' => false,
                    'column_width' => 1,
                    'is_system_defined' => true,
                ],
                [
                    'db_id' => '',
                    'name' => 'email',
                    'label' => 'Email Address',
                    'type' => 'email',
                    'placeholder' => 'Enter email address',
                    'options' => '',
                    'is_required' => false,
                    'show_in_table' => false,
                    'searchable' => true,
                    'filterable' => false,
                    'column_width' => 1,
                    'is_system_defined' => true,
                ],
                [
                    'db_id' => '',
                    'name' => 'username',
                    'label' => 'Username',
                    'type' => 'text',
                    'placeholder' => 'Enter your username',
                    'options' => '',
                    'is_required' => false,
                    'show_in_table' => false,
                    'searchable' => true,
                    'filterable' => false,
                    'column_width' => 1,
                    'is_system_defined' => true,
                ],
                [
                    'db_id' => '',
                    'name' => 'company',
                    'label' => 'Company',
                    'type' => 'text',
                    'placeholder' => 'Enter your company',
                    'options' => '',
                    'is_required' => false,
                    'show_in_table' => false,
                    'searchable' => true,
                    'filterable' => false,
                    'column_width' => 1,
                    'is_system_defined' => true,
                ],
                [
                    'db_id' => '',
                    'name' => 'address',
                    'label' => 'Address',
                    'type' => 'text',
                    'placeholder' => 'Enter your address',
                    'options' => '',
                    'is_required' => false,
                    'show_in_table' => false,
                    'searchable' => true,
                    'filterable' => false,
                    'column_width' => 1,
                    'is_system_defined' => true,
                ],
                [
                    'db_id' => '',
                    'name' => 'website',
                    'label' => 'Website',
                    'type' => 'text',
                    'placeholder' => 'Enter your website',
                    'options' => '',
                    'is_required' => false,
                    'show_in_table' => false,
                    'searchable' => true,
                    'filterable' => false,
                    'column_width' => 1,
                    'is_system_defined' => true,
                ],
                [
                    'db_id' => '',
                    'name' => 'description',
                    'label' => 'Description',
                    'type' => 'textarea',
                    'placeholder' => 'Enter your description',
                    'options' => '',
                    'is_required' => false,
                    'show_in_table' => false,
                    'searchable' => false,
                    'filterable' => false,
                    'column_width' => 1,
                    'is_system_defined' => true,
                ]
            ];
        } else {
            $fields = [
                [
                    'db_id' => '',
                    'name' => 'name',
                    'label' => 'Name',
                    'type' => 'text',
                    'placeholder' => 'Enter your name',
                    'options' => '',
                    'is_required' => true,
                    'show_in_table' => true,
                    'searchable' => true,
                    'filterable' => false,
                    'column_width' => 1,
                    'is_system_defined' => false,
                ]
            ];
        }

        return response()->json(['fields' => $fields]);
    }

    private function getCommonFormData(string $type): array
    {
        $tenantId = tenant('id');
        $tag = tenant_tag();

        $categories = Cache::tags([$tag])->remember("all_{$type}_categories_{$tenantId}", 3600, function () use ($type) {
            $categoryType = CategoryType::where('name', $type)->first();
            return $categoryType ? $categoryType->categories()->active()->select('id', 'name')->latest()->get() : collect();
        });

        $leadSubjects = Cache::tags([$tag])->remember("all_lead_subjects_{$tenantId}", 3600, fn () => LeadSubject::select('id', 'name')->active()->latest()->get());
        $leadSources  = Cache::tags([$tag])->remember("all_lead_sources_{$tenantId}", 3600, fn () => LeadSource::select('id', 'name')->active()->latest()->get());
        $users        = Cache::tags([$tag])->remember("all_users_{$tenantId}", 3600, fn () => User::select('id', 'name')->latest()->get());
        $statuses     = Cache::tags([$tag])->remember("all_crm_{$type}_statuses_{$tenantId}", 3600, fn () => Status::whereIn('type', [$type])->active()->select('id', 'name')->latest()->get());

        return compact('categories', 'leadSources', 'leadSubjects', 'users', 'statuses');
    }
}