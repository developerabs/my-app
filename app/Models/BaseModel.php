<?php

namespace App\Models;

use App\Traits\HasAuditTrail;
use App\Traits\HasCustomFields;
use App\Traits\LogIP;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

abstract class BaseModel extends Model
{
    // Core traits that every single model in your POS will use
    use HasAuditTrail, LogsActivity, LogIP, HasCustomFields;

    protected static function boot()
    {
        parent::boot();
        // static::bootHasAuditTrail();
    }

    /**
     * English: Global Activity Log setup.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * English: Global scope for active status.
     */
    public function scopeActive($query)
    {
        if(Schema::hasColumn($this->getTable(), 'is_active')){
            return $query->where('is_active', true);
        }

        return $query;
    }

    public function creator()
    {
        if(Schema::hasColumn($this->getTable(), 'created_by')){
            return $this->belongsTo(User::class, 'created_by');
        }
        return null;
    }

    public function updater()
    {
        if(Schema::hasColumn($this->getTable(), 'updated_by')){
            return $this->belongsTo(User::class, 'updated_by');
        }

        return null;
    }

    public function deleter()
    {
        if (Schema::hasColumn($this->getTable(), 'deleted_by')) {
            return $this->belongsTo(User::class, 'deleted_by');
        }

        return null;
    }

    /**
     * Virtual String ID Accessor to fix PostgreSQL Polymorphic Type Mismatch (varchar = integer)
     */
    public function getStringIdAttribute(): string
    {
        return (string) ($this->attributes[$this->getKeyName()] ?? $this->getKey());
    }

    /**
     * Global Polymorphic Custom Field Values Relation (Safe for PostgreSQL)
     */
    public function customFieldValues()
    {
        return $this->morphMany(
            CustomFieldValue::class,
            'fieldable',
            'fieldable_type',
            'fieldable_id'
        );
    }
}
