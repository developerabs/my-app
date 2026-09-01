<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shelf extends Model
{
    protected $fillable = [
        'rack_id',
        'name',
        'code',
        'description',
    ];

    public function rack()
    {
        return $this->belongsTo(Rack::class);
    }
}
