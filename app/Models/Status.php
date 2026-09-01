<?php

namespace App\Models;

use App\Enums\StatusType;
use App\Traits\HasTrash;
use App\Traits\HasUniqueSlug;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Status extends BaseModel
{
    use SoftDeletes, HasTrash, HasUniqueSlug;
    
    protected $fillable = [
        'name',
        'slug',
        'type',
        'category_id',
        'sort_order',
        'color',
        'progress',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'name' => 'string',
        'slug' => 'string',
        'type' => StatusType::class,
        'category_id' => 'string',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'color' => 'string',
        'progress' => 'string',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_by' => 'integer',
    ];

    public function leads()
    {
        return $this->hasMany(Lead::class, 'status_id');
    }

    public function deals()
    {
        return $this->hasMany(Deal::class, 'status_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    protected static function booted()
    {
        parent::booted();

        $clearUICache = function ($model) {
            Cache::tags([tenant_tag()])->forget('all_lead_categories_' . tenant('id'));
            Cache::tags([tenant_tag()])->forget('all_deal_categories_' . tenant('id'));
            Cache::tags([tenant_tag()])->forget('all_crm_lead_statuses_' . tenant('id'));
            Cache::tags([tenant_tag()])->forget('all_crm_deal_statuses_' . tenant('id'));
            Cache::tags([tenant_tag()])->forget('all_crm_leadDeal_statuses_' . tenant('id'));
        };

        static::saved($clearUICache);
        static::deleted($clearUICache);
    }
}
