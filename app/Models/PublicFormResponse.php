<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PublicFormResponse extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'public_form_id', 'response_data', 'ip_address', 'user_agent', 'meta', 'lead_id',
    ];

    protected $casts = [
        'response_data' => 'array',
        'meta' => 'array',
    ];

    public function publicForm()
    {
        return $this->belongsTo(PublicForm::class);
    }

    
}
