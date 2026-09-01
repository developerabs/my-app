<?php

namespace App\Models;

use App\Contracts\RestorableConflictInterface;
use App\Traits\HasTrash;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Membership extends BaseModel implements RestorableConflictInterface
{
    use HasUuids, SoftDeletes, HasTrash;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'code',
        'membership_fee',
        'minimum_spend',
        'validation_days',
        'discount_type',
        'discount_value',
        'benefits',
        'is_active',
    ];

    protected $casts = [
        'benefits' => 'array',
        'is_active' => 'boolean',
    ];

    public function hasRestorationConflict(): bool
    {
        return self::where('code', $this->code)
            ->whereNull('deleted_at')
            ->exists();
    }
}
