<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Trash extends Model
{
    protected $fillable = ['trashable_type', 'trashable_id', 'name', 'deleted_by'];

    public function trashable(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }

    public function deleter(): BelongsTo
    {
        // Relationship to the User model
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
