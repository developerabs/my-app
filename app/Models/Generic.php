<?php

namespace App\Models;

use App\Contracts\RestorableConflictInterface;
use App\Traits\HasTrash;
use App\Traits\HasUniqueSlug;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Generic extends BaseModel implements RestorableConflictInterface
{
    use HasUuids, SoftDeletes, HasTrash, HasUniqueSlug;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'sort_order',
        'is_active',
        'is_featured',
        'external_id',
        'source_from',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    protected static function booted()
    {
        parent::booted();

        $clearCache = function(){
            Cache::tags([tenant_tag()])->forget('generics_' . tenant('id'));
        };

        static::saved($clearCache);
        static::deleted($clearCache);
    }

    public function hasRestorationConflict(): bool
    {
        return self::where('name', $this->name)
            ->whereNull('deleted_at')
            ->exists();
    }
    //
}
