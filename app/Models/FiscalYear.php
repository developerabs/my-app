<?php

namespace App\Models;

use App\Contracts\RestorableConflictInterface;
use App\Enums\PeriodStatus;
use App\Traits\HasTrash;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FiscalYear extends BaseModel implements RestorableConflictInterface
{
    use SoftDeletes, HasTrash;

    protected $fillable = [
        'name', 'code', 'start_date', 'end_date', 'status', 'notes', 'allow_adjustment_entries', 'closed_at', 'closed_by', 'created_by', 'updated_by', 'deleted_by'
    ];

    protected $casts = [
        'status' => PeriodStatus::class,
        'allow_adjustment_entries' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function periods()
    {
        return $this->hasMany(AccountingPeriod::class);
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function hasRestorationConflict(): bool
    {
        return self::where('name', $this->name)
            ->where('code', $this->code)
            ->whereNull('deleted_at')
            ->exists();
    }
}
