<?php

namespace App\Models;

use App\Contracts\FeatureLimitInterface;
use App\Contracts\RestorableConflictInterface;
use App\Traits\HasFeatureLimit;
use App\Traits\HasFiles;
use App\Traits\HasTrash;
use App\Traits\HasUniqueSlug;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Category extends BaseModel implements RestorableConflictInterface, FeatureLimitInterface
{
    // HasFeatureLimit এখন অটোমেটিক লিমিট কাউন্ট (Increment/Decrement) ম্যানেজ করবে
    use HasUuids, SoftDeletes, HasFiles, HasTrash, HasUniqueSlug, HasFeatureLimit;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'parent_id', 'external_id', 'source_from', 'name', 'slug', 
        'description', 'image', 'sort_order', 'is_active', 'is_featured', 
        'meta_title', 'meta_description', 'category_type_id', 
        'created_by', 'updated_by', 'deleted_by'
    ];

    protected static function booted()
    {
        parent::booted();

        // শুধুমাত্র UI লিস্ট ক্যাশ ক্লিয়ার করার জন্য ক্লোজার
        $clearUICache = function ($model) {
            Cache::tags([tenant_tag()])->forget('product_categories_' . tenant('id'));
            Cache::tags([tenant_tag()])->forget('all_lead_categories_' . tenant('id'));
            Cache::tags([tenant_tag()])->forget('all_deal_categories_' . tenant('id'));
            Cache::tags([tenant_tag()])->forget('all_crm_lead_statuses_' . tenant('id'));
            Cache::tags([tenant_tag()])->forget('all_crm_deal_statuses_' . tenant('id'));
            Cache::tags([tenant_tag()])->forget('all_crm_leadDeal_statuses_' . tenant('id'));
        };

        static::saved($clearUICache);
        static::deleted($clearUICache);

        static::forceDeleted(function ($category) {
            // S3 থেকে ইমেজ ডিলিট করা
            if ($category->image) {
                $category->deleteFile($category->image, 's3');
                $category->deleteFile($category->getThumbnailPath($category->image), 's3');
            }

            Cache::tags([tenant_tag()])->forget('product_categories_' . tenant('id'));
        });
    }

    /**
     * Restoration conflict check
     */
    public function hasRestorationConflict(): bool
    {
        return self::where('name', $this->name)
            ->where('category_type_id', $this->category_type_id)
            ->whereNull('deleted_at')
            ->exists();
    }

    public function getFeatureLimitKey(): string
    {
        return 'categories_limit';
    }

    /**
     * Thumbnail Accessor.
     */
    public function getThumbUrlAttribute(): string
    {
        if (!$this->image) return file_url(null);

        $thumbPath = $this->getThumbnailPath($this->image);
        return $this->getFileUrl($thumbPath, 's3');
    }



    // --- Relationships ---

    public function type(): BelongsTo
    {
        return $this->belongsTo(CategoryType::class, 'category_type_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order', 'asc');
    }

    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_category_product', 'category_id', 'product_id');
    }

    public function leads()
    {
        return $this->hasMany(Lead::class, 'category_id');
    }

    // --- Scopes ---
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}