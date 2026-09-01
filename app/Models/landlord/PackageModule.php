<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;

class PackageModule extends Model
{
    protected $fillable = [
        'package_id',
        'module_id',
        'is_active',
        'meta',
    ];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
