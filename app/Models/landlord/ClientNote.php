<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ClientNote extends Model
{
    protected $fillable = [
        'tenant_id',
        'reseller_client_id',
        'note',
        'added_by'
    ];

    public function user() {
        return $this->belongsTo(User::class, 'added_by');
    }

}
