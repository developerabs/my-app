<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Models\Category;
use App\Models\LeadSource;
use App\Models\LeadSubject;
use App\Models\Note;
use App\Models\Status;
use App\Models\User;
use App\Traits\HasFiles;
use App\Traits\HasTrash;
use App\Traits\HasUniqueSlug;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends BaseModel
{
    use SoftDeletes, HasTrash, HasUniqueSlug, HasFiles;
    
    protected $fillable = [
        'type',
        'category_id',
        'lead_subject_id',
        'lead_source_id',
        'status_id',
        'created_by',
        'updated_by',
        'deleted_by',
        'manager_id',
        'assigned_to_id',
        'name',
        'company_name',
        'phone',
        'effective_phone',
        'email',
        'username',
        'description',
        'address',
        'website',
        'priority',
        'attachment',
        'expected_value',
        'follow_up_date',
        'customer_id',
        'converted_at',
        'failed_at',
        'is_failed'
    ];

    protected $casts = [
        'category_id' => 'string',
        'lead_subject_id' => 'integer',
        'lead_source_id' => 'integer',
        'status_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_by' => 'integer',
        'manager_id' => 'integer',
        'assigned_to_id' => 'integer',
        'name' => 'string',
        'company_name' => 'string',
        'phone' => 'string',
        'effective_phone' => 'string',
        'email' => 'string',
        'username' => 'string',
        'description' => 'string',
        'address' => 'array',
        'website' => 'string',
        'priority' => 'string',
        'attachment' => 'string',
        'expected_value' => 'decimal:2',
        'follow_up_date' => 'datetime',
        'customer_id' => 'string',
        'converted_at' => 'datetime',
        'failed_at' => 'datetime',
        'is_failed' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function leadSubject()
    {
        return $this->belongsTo(LeadSubject::class, 'lead_subject_id');
    }

    public function leadSource()
    {
        return $this->belongsTo(LeadSource::class, 'lead_source_id');
    }

    public function leadStatus()
    {
        return $this->belongsTo(Status::class, 'status_id', 'id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable', 'noteable_type', 'noteable_id')
            ->latest('created_at');
    }

    public function meetings(): MorphMany
    {
        return $this->morphMany(Meeting::class, 'meetingable', 'meetingable_type', 'meetingable_id');
    }

    /**
     * Get the latest note using morphOne
     */
    public function latestNote(): MorphOne
    {
        return $this->morphOne(Note::class, 'noteable', 'noteable_type', 'noteable_id')
            ->latestOfMany('created_at');
    }

    /**
     * Get the latest meeting using morphOne
     */
    public function latestMeeting(): MorphOne
    {
        return $this->morphOne(Meeting::class, 'meetingable', 'meetingable_type', 'meetingable_id')
            ->latestOfMany('start_at');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_id', 'id');
    }

}

