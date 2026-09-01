<?php

namespace App\Models;

use App\Enums\ImeiEventType;
use Illuminate\Database\Eloquent\Model;

class ProductImeiLog extends Model
{
    protected $fillable = [
        'product_imei_id', 'branch_id', 'event_type', 'description', 'causable_type', 'causable_id', 'user_id',
    ];

    protected $casts = [
        'event_type' => ImeiEventType::class,
    ];

    public function productImei()
    {
        return $this->belongsTo(ProductImei::class, 'product_imei_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function causable()
    {
        return $this->morphTo();
    }
}
