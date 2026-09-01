<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'content',
        'status',
        'settings',
        'meta',
    ];

    protected $casts = [
        'settings' => 'array',
        'meta' => 'array',
    ];

    public function scopePublished($query)
    {
        return $query->where('status', '=', 'published');
    }
}
