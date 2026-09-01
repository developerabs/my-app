<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasFiles;

class ProductVariantImage extends Model
{
    use HasFiles;
    
    protected $fillable = ['variant_id', 'image_path', 'sort_order', 'alt_text', 'is_primary'];

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
