<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class HomepageWidget extends Model
{
    protected $connection = 'sherazipos_landlord';

    protected $fillable = [
        'title',
        'subtitle',
        'type',
        'content_type',
        'content',
        'settings',
        'sort_order',
        'is_enabled',
        'is_editable',
        'is_global',
    ];

    protected $casts = [
        'content' => 'array',
        'settings' => 'array',
    ];

    public static function boot()
    {
        parent::boot();
        static::saved(function ($model) {
            static::clearWidgetCache($model);
        });

        // ডেটা ডিলিট হলেও ক্যাশ ক্লিয়ার হবে
        static::deleted(function ($model) {
            static::clearWidgetCache($model);
        });
    }

    protected static function clearWidgetCache($model)
    {
        if ($model->type === 'header') {
            Cache::tags([landlord_tag()])->forget('landlordHeader');
        }

        if ($model->type === 'footer') {
            Cache::tags([landlord_tag()])->forget('landlordFooter');
        }

        // যদি আপনি চান যেকোনো পরিবর্তনে পুরো ল্যান্ডলর্ড উইজেট ক্যাশ চলে যাক
        // Cache::tags([landlord_tag()])->forget('landlordHeader');
        // Cache::tags([landlord_tag()])->forget('landlordFooter');
    }
}
