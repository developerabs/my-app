<?php

namespace App\Models;

use App\Contracts\RestorableConflictInterface;
use App\Traits\HasFiles;
use App\Traits\HasTrash;
use App\Traits\HasUniqueSlug;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Brand extends BaseModel implements RestorableConflictInterface
{
    use HasUuids, HasFiles, SoftDeletes, HasTrash, HasUniqueSlug;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'slug',
        'website_url',
        'description',
        'brand_logo',
        'cover_image',
        'sort_order',
        'is_active',
        'is_featured',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'external_id',
        'source_from',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected static function booted()
    {
        parent::booted();

        $clearCache = function(){
            Cache::tags([tenant_tag()])->forget('brands_' . tenant('id'));
        };

        static::saved($clearCache);
        static::deleted($clearCache);

        static::forceDeleted(function ($brand) {
            // deleteFile from HasFiles trait now handles S3 deletion and Cache clearing
            if ($brand->brand_logo) {
                $brand->deleteFile($brand->brand_logo, 's3');
                $brand->deleteFile($brand->getThumbnailPath($brand->brand_logo), 's3');
            }

            if ($brand->cover_image) {
                $brand->deleteFile($brand->cover_image, 's3');
            }

            Cache::tags([tenant_tag()])->forget('brands_' . tenant('id'));
        });
    }

    public function getThumbUrlAttribute(): string
    {
        if (!$this->brand_logo) {
            return url('images/preview_image.png'); 
        }

        $thumbPath = $this->getThumbnailPath($this->brand_logo);

        return $this->getFileUrl($thumbPath, 's3');
    }
    public function hasRestorationConflict(): bool
    {
        return self::where('name', $this->name)
            ->whereNull('deleted_at')
            ->exists();
    }
}
