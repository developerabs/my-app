<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryType extends Model
{
    protected $fillable = [
        'name',
        'display_name',
    ];

    public function categories()
    {
        return $this->hasMany(Category::class, 'category_type_id');
    }
}
