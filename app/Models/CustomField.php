<?php

namespace App\Models;

use App\Contracts\RestorableConflictInterface;
use App\Traits\HasAuditTrail;
use App\Traits\HasTrash;
use App\Traits\LogIP;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CustomField extends Model implements RestorableConflictInterface
{
    use SoftDeletes, HasTrash, HasAuditTrail, LogsActivity, LogIP;
    protected $fillable = [
        'model_type',
        'label',
        'name',
        'type',
        'options',
        'default_value',
        'placeholder',
        'is_required',
        'order',
        'show_in_list',
        'is_active',
    ];

    protected $casts = [
        'options' => 'array', // JSON অপশনগুলোকে অটোমেটিক অ্যারেতে কনভার্ট করবে
        'is_required' => 'boolean',
        'show_in_list' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::bootHasAuditTrail();

        $clearCache = function($customField){
            $modelName = strtolower(class_basename($customField->model_type));
            $cacheKey = "custom_fields_{$modelName}_" . tenant('id');
            Cache::tags([tenant_tag()])->forget($cacheKey);
        };

        static::saved($clearCache);
        static::deleted($clearCache);

        static::creating(function ($field) {
            if (empty($field->name)) {
                $field->name = Str::slug($field->label, '_');
            }
        });
    }

    // এই ফিল্ডের অধীনে যত ভ্যালু আছে
    public function values()
    {
        return $this->hasMany(CustomFieldValue::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function hasRestorationConflict(): bool
    {
        return self::where('model_type', $this->model_type)
            ->where('name', $this->name)
            ->exists();
    }
}
