<?php

namespace App\Models;

use App\Contracts\RestorableConflictInterface;
use App\Traits\HasTrash;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Tax extends BaseModel implements RestorableConflictInterface
{
    use HasUuids, SoftDeletes, HasTrash;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'rate',
        'is_active',
        'external_id',
        'source_from',
        'meta',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    protected static function booted()
    {
        parent::booted();

        $clearUiCache = function ($model) {
            Cache::tags([tenant_tag()])->forget('all_taxes_' . tenant('id'));
            Cache::tags([tenant_tag()])->forget('taxes_active_' . tenant('id'));
        };

        static::saved($clearUiCache);
        static::updated($clearUiCache);
        static::deleted($clearUiCache);
    }

    public function hasRestorationConflict(): bool
    {
        return self::where('name', $this->name)
            ->whereNull('deleted_at')
            ->exists();
    }
}
