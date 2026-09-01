<?php

namespace App\Models;

use App\Traits\HasFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerDetails extends Model
{
    use HasFiles;
    
    protected $fillable = [
        'customer_id',
        'company_name',
        'tax_number',
        'date_of_birth',
        'description',
        'gender',
        'image',
    ];

    /**
     * Relationship back to Customer
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
