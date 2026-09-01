<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasFiles;

class ProductImage extends Model
{
    use HasFiles;
    
    protected $fillable = [
        'product_id',
        'image',
        'sort_order',
        'alt_text',
        'metadata',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
