<?php

namespace App\Models;

use App\Traits\HasTrash;
use App\Traits\HasUniqueSlug;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class LeadSource extends BaseModel
{
    use SoftDeletes, HasTrash, HasUniqueSlug;
    
    protected $fillable = [
        'name',
        'slug',
        'sort_order',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'name' => 'string',
        'slug' => 'string',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_by' => 'integer',
    ];

    public function leads()
    {
        return $this->hasMany(Lead::class, 'lead_source_id');
    }
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    protected static function booted()
    {
        parent::booted();

        // শুধুমাত্র UI লিস্ট ক্যাশ ক্লিয়ার করার জন্য ক্লোজার
        $clearUICache = function ($model) {
            Cache::tags([tenant_tag()])->forget('all_lead_sources_' . tenant('id'));
        };

        static::saved($clearUICache);
        static::deleted($clearUICache);
    }
}
