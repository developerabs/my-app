<?php

namespace App\Models;

use App\Contracts\FeatureLimitInterface;
use App\Contracts\RestorableConflictInterface;
use App\Traits\HasFeatureLimit;
use App\Traits\HasFiles;
use App\Traits\HasTrash;
use App\Traits\HasUniqueSlug;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Branch extends BaseModel implements RestorableConflictInterface, FeatureLimitInterface
{
    use HasUuids, SoftDeletes, HasTrash, HasFiles, HasUniqueSlug, HasFeatureLimit;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name', 'slug', 'branch_code', 'address', 'phone', 'email', 
        'currency_id', 'timezone', 'bin_number', 'branch_logo', 'branch_settings', 
        'is_active', 'is_default', 'status', 'locked_at', 
        'created_by', 'updated_by', 'deleted_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    protected static function booted()
    {
        parent::booted();

        $clearUiCache = function ($model) {
            Cache::tags([tenant_tag()])->forget('all_active_branches_' . tenant('id'));
        };

        static::saved($clearUiCache);
        static::deleted($clearUiCache);

        static::forceDeleted(function ($branch) {
            if ($branch->branch_logo) {
                $branch->deleteFile($branch->branch_logo, 's3');
            }
            Cache::tags([tenant_tag()])->forget('all_active_branches_' . tenant('id'));
        });
    }

    // 🟢 Currency Relationship
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    // 🟢 All Accounts of this Branch (1 to Many)
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'branch_id');
    }

    // 🟢 Default Operating Account of this Branch (Managed via accounts.is_default = true)
    public function defaultAccount(): HasOne
    {
        return $this->hasOne(Account::class, 'branch_id')->where('is_default', true);
    }

    public function hasRestorationConflict(): bool
    {
        return self::where('name', $this->name)
            ->whereNull('deleted_at')
            ->exists();
    }

    public function getFeatureLimitKey(): string
    {
        return 'branches_limit';
    }
}