<?php

namespace App\Models;

use App\Contracts\FeatureLimitInterface;
use App\Contracts\RestorableConflictInterface;
use App\Enums\AssetEntryType;
use App\Enums\DepreciationMethod;
use App\Traits\HasTrash;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Asset extends BaseModel implements FeatureLimitInterface, RestorableConflictInterface
{
    use HasTrash, SoftDeletes;

    protected $fillable = [
        'account_id',
        'asset_code',
        'asset_name',
        'unit',
        'is_depreciable',
        'depreciation_method',
        'is_active',
        'notes',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_depreciable' => 'boolean',
        'is_active' => 'boolean',
        'depreciation_method' => DepreciationMethod::class,
    ];

    public static function booted()
    {
        static::saved(fn ($model) => self::clearUiCache());
        static::updated(fn ($model) => self::clearUiCache());
        static::deleted(fn ($model) => self::clearUiCache());
    }

    public static function clearUiCache()
    {
        Cache::tags([tenant_tag()])->forget('all_assets_'.tenant('id'));
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function registerItems()
    {
        return $this->hasMany(AssetRegisterItem::class);
    }

    public function getTotalQuantityAttribute()
    {
        return $this->registerItems()
            ->whereHas('register', function ($query) {
                $query->whereIn('entry_type', [
                    AssetEntryType::OPENING,
                    AssetEntryType::PURCHASE,
                    AssetEntryType::ADJUSTMENT,
                    AssetEntryType::REVALUATION,
                ]);
            })->sum('quantity')
            -
            $this->registerItems()
                ->whereHas('register', function ($query) {
                    $query->where('entry_type', AssetEntryType::DISPOSAL);
                })->sum('quantity');
    }

    // Calculate total cost based on entry types
    public function getTotalCostAttribute()
    {
        return $this->registerItems()
            ->whereHas('register', function ($query) {
                $query->whereIn('entry_type', [
                    AssetEntryType::OPENING,
                    AssetEntryType::PURCHASE,
                    AssetEntryType::ADJUSTMENT,
                    AssetEntryType::REVALUATION,
                ]);
            })->sum('total_cost')
            -
            $this->registerItems()
                ->whereHas('register', function ($query) {
                    $query->where('entry_type', AssetEntryType::DISPOSAL);
                })->sum('total_cost');
    }

    public function hasRestorationConflict(): bool
    {
        return self::where('asset_name', $this->asset_name)
            ->where('account_id', $this->account_id)
            ->whereNull('deleted_at')
            ->exists();
    }

    public function getFeatureLimitKey(): string
    {
        return 'assets_limit';
    }
}
