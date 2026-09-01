<?php

namespace App\Models;

use App\Contracts\RestorableConflictInterface;
use App\Enums\PeriodStatus;
use App\Traits\HasTrash;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountingPeriod extends BaseModel implements RestorableConflictInterface
{
    use SoftDeletes, HasTrash;

    protected $fillable = [
        'fiscal_year_id',
        'name',
        'period_no',
        'start_date',
        'end_date',
        'status',
        'closed_at',
        'closed_by',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => PeriodStatus::class,
    ];

    public function fiscalYear()
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function HasRestorationConflict() : bool
    {
        return self::where('name', $this->name)
            ->where('fiscal_year_id', $this->fiscal_year_id)
            ->where('period_no', $this->period_no)
            ->whereNull('deleted_at')
            ->exists();
    }
}
