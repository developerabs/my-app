<?php

namespace App\Models;

use App\Contracts\RestorableConflictInterface;
use App\Traits\HasTrash;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class UnitGroup extends BaseModel implements RestorableConflictInterface
{
    use HasUuids, SoftDeletes, HasTrash;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name', 'description', 'created_by', 'updated_by', 'deleted_by'
    ];
    protected static function booted()
    {
        parent::booted();

        $clearCache = function () {
            $cacheKey = 'unitGroups_' . tenant('id');
            Cache::tags([tenant_tag()])->forget($cacheKey);
        };

        static::created($clearCache);
        static::updated($clearCache);
        static::deleted($clearCache);

        static::deleting(function ($group) {
            // English comment: Cascading soft delete for child units.
            if ($group->isForceDeleting()) {
                $group->units()->forceDelete();
            } else {
                $group->units()->delete();
            }
        });

        static::restoring(function ($group) {
            // English comment: Restore all soft-deleted child units.
            $group->units()->withTrashed()->restore();
        });
    }

    public function units()
    {
        return $this->hasMany(Unit::class, 'group_id');
    }

    public function baseUnits()
    {
        return $this->hasMany(Unit::class, 'group_id')->where('is_base_unit', true);
    }

    public function hasRestorationConflict(): bool
    {
        return self::where('name', $this->name)
            ->whereNull('deleted_at')
            ->exists();
    }

    
}
