<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Union extends Model
{
    protected $fillable = ['upazila_id', 'name', 'bn_name', 'url'];

    public function upazila()
    {
        return $this->belongsTo(Upazila::class);
    }
}
