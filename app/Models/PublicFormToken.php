<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicFormToken extends Model
{
    protected $fillable = [
        'public_form_id',
        'token_hash',
        'token_encrypted',
        'expires_at',
        'is_used',
        'used_at',
        'ip_address',
    ];

    protected $casts = [
        'public_form_id' => 'integer',
        'expires_at' => 'datetime',
        'is_used' => 'boolean',
        'used_at' => 'datetime',
    ];

    public function publicForm()
    {
        return $this->belongsTo(PublicForm::class);
    }
}