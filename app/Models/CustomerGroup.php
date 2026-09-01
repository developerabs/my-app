<?php

namespace App\Models;

use App\Contracts\RestorableConflictInterface;
use App\Traits\HasTrash;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerGroup extends BaseModel implements RestorableConflictInterface
{
    use HasUuids, SoftDeletes, HasTrash;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'description',
        'discount_type',
        'discount_value',
        'min_order_amount',
    ];

    protected $casts = [
        'discount_value' => 'float',
        'min_order_amount' => 'float',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public static function booted()
    {
        parent::booted();

        // English comment: Ensure only one default group exists
        static::saving(function ($model) {
            if ($model->is_default) {
                self::where('is_default', true)->where('id', '!=', $model->id)->update(['is_default' => false]);
            }
        });
    }

    public function hasRestorationConflict(): bool
    {
        return self::where('name', $this->name)
            ->whereNull('deleted_at')
            ->exists();
    }
}
