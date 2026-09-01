<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
     protected $fillable = [
        'title',
        'slug',
        'content',
        'status',
        'settings',
        'meta',
        'image',
    ];

    protected $casts = [
        'settings' => 'array',
        'meta' => 'array',
    ];
}
