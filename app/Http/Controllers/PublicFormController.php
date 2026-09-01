<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\PublicForm;
use App\Models\PublicFormResponse;
use App\Models\PublicFormToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Traits\HasFiles;

class PublicFormController extends Controller
{
    use HasFiles;

    public function generateToken(Request $request, PublicForm $publicForm)
    {
        abort_unless($publicForm->is_active, 404);

        $rawToken = Str::random(64);
        $expiresIn = min(max($request->integer('expires_in', 10080), 1), 43200);
        $expiresAt = now()->addMinutes($expiresIn);

        PublicFormToken::create([
            'public_form_id' => $publicForm->id,
            'token_hash' => hash('sha256', $rawToken),
            'expires_at' => $expiresAt,
        ]);

        return response()->json([
            'url' => route('public-forms.show', [$publicForm->slug, $rawToken]),
            'expires_at' => $expiresAt->toIso8601String(),
        ], 201);
    }

    public function show(string $slug, string $token)
    {
        $form = PublicForm::query()->where('slug', $slug)->where('is_active', true)->first();
        $tokenRecord = $form ? $this->validToken($form, $token) : null;

        if (!$form || !$tokenRecord) {
            return response()->view('errors.link_expired', [], 410);
        }

        return view('public_forms.view', [
            'form' => $form,
            'fields' => $this->fields($form),
            'token' => $token,
        ]);
    }

    public function submit(Request $request, string $slug, string $token)
    {
        $form = PublicForm::query()->where('slug', $slug)->where('is_active', true)->first();

        if (!$form) {
            return response()->view('errors.link_expired', [], 410);
        }

        $rules = $this->validationRules($form);
        $validated = $request->validate($rules);

        if ($request->filled('website_hp')) {
            abort(422, 'Invalid submission.');
        }
        try {
        $result = DB::transaction(function () use ($request, $form, $token, $validated) {
            $tokenRecord = PublicFormToken::query()
                ->where('public_form_id', $form->id)
                ->where('token_hash', hash('sha256', $token))
                ->lockForUpdate()
                ->first();

            if (!$tokenRecord || $tokenRecord->is_used || $tokenRecord->expires_at->isPast()) {
                return null;
            }

            $uploadedAttachment = $request->hasFile('attachment')
                ? $this->uploadFiles($request, 'attachment', 'public_form_responses')
                : null;
            
            $responseData = collect($validated)->except('website_hp')->toArray();
            if ($uploadedAttachment) {
                $responseData['attachment'] = $uploadedAttachment;
            }

            $response = PublicFormResponse::create([
                'public_form_id' => $form->id,
                'response_data' => $responseData,
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
            ]);

            $lead = null;
            if ($form->submission_mode === 'auto_lead') {
                $leadAttributes = $this->leadAttributes($form, $validated);
                
                if ($uploadedAttachment) {
                    $leadAttributes['attachment'] = $uploadedAttachment;
                }
                $lead = Lead::create($leadAttributes);
                $response->update(['lead_id' => $lead->id]);
            }

            // $tokenRecord->update([
            //     'is_used' => false,
            //     'used_at' => now(),
            //     'ip_address' => $request->ip(),
            // ]);

            return compact('lead', 'response');
        });

        if (!$result) {
            return response()->view('errors.link_expired', [], 410);
        }

        if ($form->redirect_url) {
            return redirect()->away($form->redirect_url);
        }

        $successView = data_get($form->meta, 'success_view');
        if ($successView && view()->exists($successView)) {
            return view($successView, ['form' => $form, 'lead' => $result['lead'], 'response' => $result['response']]);
        }

        return view('public_forms.success', [
            'form' => $form,
            'message' => $form->success_message ?: data_get($form->meta, 'success_message', 'Thank you. Your submission has been received.'),
        ]);
        } catch (\Exception $e) {
            return response()->view('public_forms.success', [
                'form' => $form,
                'message' => __('An error occurred while processing your submission.')
                ], 500);
        }
    }

    private function validToken(PublicForm $form, string $token): ?PublicFormToken
    {
        $record = $form->tokens()->where('token_hash', hash('sha256', $token))->first();

        return $record && !$record->is_used && $record->expires_at->isFuture() ? $record : null;
    }

    private function fields(PublicForm $form): array
    {
        return $form->fields()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($field) => [
                'name' => $field->name,
                'label' => $field->label,
                'type' => $field->type,
                'placeholder' => $field->placeholder,
                'is_required' => (bool) $field->is_default_required,
                'options' => (array) $field->options,
                'column_width' => (int) ($field->column_width ?: 1),
            ])
            ->values()
            ->all();
    }

    private function validationRules(PublicForm $form): array
    {
        $rules = ['website_hp' => ['nullable', 'string', 'max:0']];
        
        foreach ($this->fields($form) as $field) {
            $fieldRules = [$field['is_required'] ? 'required' : 'nullable'];
            
            if ($field['type'] === 'file') {
                array_push($fieldRules, 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120');
            } else {
                $fieldRules[] = match ($field['type']) {
                    'email' => 'email',
                    'number' => 'numeric',
                    'date' => 'date',
                    'select' => 'in:' . implode(',', array_map(
                        fn ($option) => is_array($option) ? (string) ($option['value'] ?? '') : (string) $option,
                        (array) ($field['options'] ?? [])
                    )),
                    'textarea' => 'string',
                    default => 'string',
                };
                
                // ফিল্ডের টাইপ অনুযায়ী max/max_digits রুল সেট করা
                if ($field['type'] === 'number') {
                    $fieldRules[] = 'max_digits:20'; // নাম্বার সর্বোচ্চ ২০ ডিজিট হতে পারবে
                } elseif (!in_array($field['type'], ['date', 'select'])) {
                    $fieldRules[] = 'max:5000'; // স্ট্রিং/টেক্সট সর্বোচ্চ ৫০০০ ক্যারেক্টার হতে পারবে
                }
            }

            if ($form->submission_mode === 'auto_lead' && $field['name'] === 'username') {
                $fieldRules[] = 'unique:leads,username';
            }

            $rules[$field['name']] = $fieldRules;
        }

        return $rules;
    }

    private function leadAttributes(PublicForm $form, array $input): array
    {
        $aliases = ['category' => 'category_id', 'source' => 'lead_source_id', 'subject' => 'lead_subject_id', 'status' => 'status_id'];
        $attributes = [];
        foreach (['type', 'name', 'phone', 'email', 'company_name', 'username', 'description', 'address', 'website', 'priority', 'expected_value', 'follow_up_date', 'category_id', 'lead_subject_id', 'lead_source_id', 'status_id', 'manager_id', 'assigned_to_id'] as $key) {
            if (array_key_exists($key, $input)) {
                $attributes[$key] = $input[$key];
            }
        }
        foreach ($aliases as $from => $to) {
            if (array_key_exists($from, $input)) {
                $attributes[$to] = $input[$from];
            }
        }
        if (array_key_exists('note', $input)) {
            $attributes['description'] = $input['note'];
        }
        if (isset($attributes['address']) && is_string($attributes['address'])) {
            $attributes['address'] = ['address' => $attributes['address']];
        }

        $attributes['type'] = $attributes['type'] ?? 'lead';
        foreach (['status_id' => 'default_status_id', 'lead_source_id' => 'default_source_id', 'lead_subject_id' => 'default_subject_id', 'category_id' => 'default_category_id', 'manager_id' => 'default_manager_id', 'assigned_to_id' => 'default_assigned_to_id'] as $leadKey => $formKey) {
            if ($form->{$formKey} !== null) {
                $attributes[$leadKey] = $form->{$formKey};
            }
        }

        return $attributes;
    }
}