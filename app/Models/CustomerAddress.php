<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends Model
{
    use HasUuids;
    
    protected $fillable = [
        'customer_id',
        'address_type',
        'is_primary',
        'full_address',
        'state',
        'city',
        'post_code',
        'upazila',
        'district',
        'division',
        'country',
        'latitude',
        'longitude',
    ];

    /**
    * Casting attributes
    */
    protected $casts = [
        'is_primary' => 'boolean',
    ];

    /**
     * Relationship back to Customer
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
