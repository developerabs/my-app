<?php

namespace App\Models;

use App\Contracts\RestorableConflictInterface;
use App\Traits\HasTrash;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Unit extends BaseModel implements RestorableConflictInterface
{
    use HasUuids, SoftDeletes, HasTrash;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'group_id',
        'base_unit_id',
        'name',
        'short_name',
        'description',
        'is_base_unit',
        'display_params',
        'operator',
        'operator_value',
        'is_formulaic',
        'formula',
        'precision'
    ];

    protected $casts = [
        'is_base_unit' => 'boolean',
        'is_formulaic' => 'boolean',
        'display_params' => 'array',
        'operator_value' => 'float',
        'precision' => 'integer',
    ];

    public static function booted()
    {
        parent::booted();

        $clearCache = function () {
            $cacheKey = 'unitGroups_' . tenant('id');
            Cache::tags([tenant_tag()])->forget($cacheKey);
        };
        static::created($clearCache);
        static::updated($clearCache);
        static::deleted($clearCache);
    }

    // English comment: Dynamic accessor to sort hierarchy from largest to smallest unit
    public function getDisplayHierarchyTextAttribute()
    {
        $hierarchyIds = $this->display_params['hierarchy'] ?? [];

        $currentDisplayName = !empty($this->short_name) ? $this->short_name : $this->name;

        if (empty($hierarchyIds)) {
            return $currentDisplayName;
        }

        // eager load করে নিয়ে আসা যাতে calculateDepth ডাটাবেস হিট না করে
        $units = self::whereIn('id', $hierarchyIds)
                ->where('id', '!=', $this->id) // ডুপ্লিকেট এড়াতে নিজেকে বাদ দেওয়া
                ->get();

        $hierarchyNames = $units->map(function ($unit) {
            return [
                'display_name' => !empty($unit->short_name) ? $unit->short_name : $unit->name,
                'depth' => $this->calculateDepth($unit)
            ];
        })
        ->sortByDesc('depth') // ডেপথ অনুযায়ী বড় থেকে ছোট সাজানো (Box > Piece)
        ->pluck('display_name');

        return $hierarchyNames->push($currentDisplayName)->implode(' > ');
    }

    /**
     * English comment: Helper function to calculate how far a unit is from the base unit.
     * Larger units like Carton will have a higher depth than Piece.
     */
    private function calculateDepth($unit)
    {
        $depth = 0;
        // load('baseUnit') অলরেডি করা থাকলে এটি ডাটাবেসে হিট করবে না
        while ($unit && $unit->base_unit_id) {
            $depth++;
            // যদি eager load করা থাকে তবে $unit->baseUnit সরাসরি মেমোরি থেকে ডাটা দিবে
            $unit = $unit->baseUnit;
        }
        return $depth;
    }

    public function scopeIsBase($query)
    {
        return $query->where('is_base_unit', true);
    }
    public function group()
    {
        return $this->belongsTo(UnitGroup::class, 'group_id');
    }

    public function baseUnit()
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function subUnits()
    {
        return $this->hasMany(Unit::class, 'base_unit_id');
    }

    public function allSubUnits()
    {
        // English: Self-referential hasMany relationship for recursive children
        return $this->hasMany(Unit::class, 'base_unit_id', 'id')->with('allSubUnits');
    }
    /**
     * Helper to calculate base quantity.
     * English comment: Use this method to convert any unit quantity to the base unit quantity.
     */
    public function convertToBase($quantity, array $context = [])
    {
        // সার্ভিসটিকে সরাসরি কল করা যাতে ফর্মুলা লজিক ডুপ্লিকেট না হয়
        return app(\App\Services\UnitFormulaService::class)
            ->getFinalBaseQuantity($quantity, $this, $context);
    }

    public function hasRestorationConflict(): bool
    {
        return self::where('name', $this->name)
            ->whereNull('deleted_at')
            ->exists();
    }
}
