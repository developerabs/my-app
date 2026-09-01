<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Models\PublicForm;
use Illuminate\Database\Eloquent\SoftDeletes;

class PublicFormField extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'public_form_id', 'name', 'label', 'type', 'options', 'placeholder',
        'is_default_required', 'is_system_defined', 'is_active', 'sort_order', 'column_width', 'show_in_table', 'searchable', 'filterable',
    ];

    protected $casts = [
        'options' => 'array',
        'is_default_required' => 'boolean',
        'is_system_defined' => 'boolean',
        'is_active' => 'boolean',
        'column_width' => 'integer',
        'show_in_table' => 'boolean',
        'searchable' => 'boolean',
        'filterable' => 'boolean',
    ];

    public function publicForm()
    {
        return $this->belongsTo(PublicForm::class);
    }
}
