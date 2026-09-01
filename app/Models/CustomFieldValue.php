<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomFieldValue extends Model
{
    protected $fillable = [
        'custom_field_id',
        'fieldable_id',
        'fieldable_type',
        'value',
    ];

    /* 
       Polymorphic relation allows this model to belong to 
       Product, Customer, or any other defined model type.
    */
    public function fieldable()
    {
        return $this->morphTo();
    }

    // কোন ফিল্ড ডেফিনিশনের আন্ডারে এই ভ্যালু
    public function definition()
    {
        return $this->belongsTo(CustomField::class, 'custom_field_id');
    }

    public function customField()
    {
        return $this->belongsTo(CustomField::class, 'custom_field_id');
    }
}
