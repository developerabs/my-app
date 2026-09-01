<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rack extends Model
{
    protected $fillable = [
        'branch_id',
        'name',
        'code',
        'description',
    ];

    public function shelves()
    {
        return $this->hasMany(Shelf::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
