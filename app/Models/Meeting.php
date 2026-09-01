<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Enums\MeetingType;

class Meeting extends BaseModel
{
    protected $fillable = [
        'meetingable_type',
        'meetingable_id',
        'category_id',
        'title',
        'description',
        'start_at',
        'end_at',
        'type',
        'location',
        'meeting_link',
        'status_id',
        'reminder_at',
        'reminder_sent',
        'assigned_to_id',
        'created_by',
        'updated_by',
        'deleted_by',
        'is_completed',
        'completed_at',
        'completed_by',
        'completion_notes',
    ];

    protected $casts = [
        'category_id' => 'string',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'reminder_at' => 'datetime',
        'reminder_sent' => 'boolean',
        'type' => MeetingType::class,
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function meetingable(): MorphTo
    {
        return $this->morphTo();
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
