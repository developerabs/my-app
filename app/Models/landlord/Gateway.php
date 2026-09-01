<?php

namespace App\Models\landlord;

use App\Models\User;
use App\Traits\HasFiles;
use Illuminate\Database\Eloquent\Model;

class Gateway extends Model
{
    use HasFiles;
    
    protected $fillable = [
        'type',
        'name',
        'display_name',
        'credentials',
        'is_active',
        'logo',
        'is_default',
        'added_by',
    ];

    protected $casts = [
        'credentials' => 'array',
    ];

    public function getCredentialsAttribute($value)
    {
        return json_decode(decrypt($value), true);
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
