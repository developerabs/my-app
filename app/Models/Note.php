<?php

namespace App\Models;

use App\Models\Status;
use App\Models\User;
use App\Traits\HasFiles;
use App\Traits\HasTrash;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Note extends BaseModel
{
    use SoftDeletes, HasTrash, HasFiles;
    /**
     * The table associated with the model.
     */
    protected $table = 'notes';

    protected $fillable = [
        'noteable_type',
        'noteable_id',
        'created_by',
        'updated_by',
        'deleted_by',
        'note_type',
        'note',
        'attachment',
        'status_id',
        'next_follow_up_at',
        'effective_phone',
        'is_meeting_set',
    ];

    protected $casts = [
        'next_follow_up_at' => 'datetime',
        'is_meeting_set' => 'boolean',
    ];

    public function model()
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function noteStatus(): BelongsTo
    {
        return $this->status();
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        // Ensure HasFiles initialization ran so fileCacheTag is set
        if (method_exists($this, 'initializeHasFiles')) {
            $this->initializeHasFiles();
        }

        $path = $this->attributes['attachment'] ?? null;

        if (!$path) {
            return null;
        }

        if (method_exists($this, 'getFileUrl')) {
            return $this->getFileUrl($path, 's3', null);
        }

        return function_exists('file_url') ? file_url($path, 's3') : null;
    }
}

