<?php

namespace App\Models;

use App\Contracts\RestorableConflictInterface;
use App\Enums\AccountType;
use App\Enums\BalanceType;
use App\Traits\HasTrash;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChartOfAccount extends BaseModel implements RestorableConflictInterface
{
    use HasTrash, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'account_type',
        'parent_id',
        'is_leaf',
        'balance_type',
        'is_system',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'account_type' => AccountType::class,
        'balance_type' => BalanceType::class,
        'is_leaf' => 'boolean',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        parent::booted();
    }

    public function requiresSubLedger(): bool
    {
        return in_array($this->code, [
            '2110',
            '1120',
            '1190',
            '2180',
            '1150',
        ]);
    }

    public function parent()
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_id')->orderBy('code');
    }

    public function childrenRecursive()
    {
        return $this->children()
            ->with('childrenRecursive');
    }

    public function accounts()
    {
        return $this->hasMany(
            Account::class,
            'chart_of_account_id'
        )
            ->where('is_active', true);
    }

    public function hasRestorationConflict(): bool
    {
        return self::where('code', $this->code)
            ->whereNull('deleted_at')
            ->exists();
    }
}
