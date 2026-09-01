<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;

class PackageFeature extends Model
{
    protected $fillable = [
        'package_id',
        'feature_id',
        'meta',
        'is_active',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function feature()
    {
        return $this->belongsTo(Feature::class);
    }

    public static function getFeaturesWithLimits($packageId)
    {
        return self::query()
            ->join('features', 'package_features.feature_id', '=', 'features.id')
            ->where('package_id', $packageId)
            ->whereNotNull('package_features.meta')
            ->select('package_features.*', 'features.key')
            ->get()
            ->filter(function ($item) {
                // মডেলে কাস্টিং থাকলে সরাসরি অ্যারে চেক করলেই হয়
                return isset($item->meta['limit']);
            });
    }
}
