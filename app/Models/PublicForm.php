<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Models\PublicFormField;
use App\Models\PublicFormResponse;
use App\Models\PublicFormToken;
use App\Models\User;
use App\Traits\HasFiles;
use App\Traits\HasTrash;
use App\Traits\HasUniqueSlug;
use Illuminate\Database\Eloquent\SoftDeletes;

class PublicForm extends BaseModel
{
    use SoftDeletes, HasTrash, HasUniqueSlug, HasFiles;

    protected $fillable = [
        'category_id','title', 'subtitle', 'slug', 'submitted_for', 'custom_logo', 'submit_button_text',
        'model_type', 'default_status_id', 'default_source_id', 'default_subject_id',
        'default_category_id', 'default_manager_id', 'default_assigned_to_id',
        'success_message', 'redirect_url', 'submission_mode', 'meta', 'created_by',
        'updated_by', 'deleted_by', 'is_active',
    ];

    protected $casts = [
        'meta' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        parent::booted();
    }

    public function tokens()
    {
        return $this->hasMany(PublicFormToken::class);
    }

    public function fields()
    {
        return $this->hasMany(PublicFormField::class)->orderBy('sort_order');
    }

    public function activeToken()
    {
        return $this->hasOne(PublicFormToken::class)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->latestOfMany();
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'default_manager_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'default_assigned_to_id');
    }

    public function formResponses()
    {
        return $this->hasMany(PublicFormResponse::class);
    }
}
