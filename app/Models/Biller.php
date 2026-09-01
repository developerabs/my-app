<?php

namespace App\Models;

use App\Contracts\FeatureLimitInterface;
use App\Contracts\RestorableConflictInterface;
use App\Traits\HasFeatureLimit;
use App\Traits\HasFiles;
use App\Traits\HasTrash;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Biller extends BaseModel implements RestorableConflictInterface, FeatureLimitInterface
{
    use HasUuids, HasFiles, SoftDeletes, HasTrash, HasFeatureLimit;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'company_name',
        'propiter_name',
        'email',
        'phone',
        'address',
        'bin',
        'logo',
        'website_url',
        'certificate',
        'tnc',
        'meta',
        'created_by',
        'updated_by',
        'deleted_by',
        'is_active'
    ];

    protected $casts = [
        'meta' => 'array',
        'is_active' => 'boolean'
    ];

    public static function booted()
    {
        parent::booted();

        $clearUiCache = function ($model) {
            Cache::tags([tenant_tag()])->forget('all_billers_' . tenant('id'));
        };

        static::saved($clearUiCache);
        static::updated($clearUiCache);
        static::deleted($clearUiCache);
        static::forceDeleted(function ($biller) {
            // deleteFile from HasFiles trait now handles S3 deletion and Cache clearing
            if ($biller->logo) {
                $biller->deleteFile($biller->logo, 's3');
            }
            if ($biller->certificate) {
                $biller->deleteFile($biller->certificate, 's3');
            }
            Cache::tags([tenant_tag()])->forget('all_billers_' . tenant('id'));
        });
    }

    public function hasRestorationConflict(): bool
    {
        return self::where('name', $this->name)
            ->whereNull('deleted_at')
            ->exists();
    }

    public function getFeatureLimitKey(): string
    {
        return 'billers_limit';
    }
}
