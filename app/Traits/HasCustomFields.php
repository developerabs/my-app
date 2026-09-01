<?php

namespace App\Traits;

use App\Models\CustomField;
use App\Models\CustomFieldValue;
use Illuminate\Support\Facades\DB;

trait HasCustomFields
{
    /**
     * Polymorphic relationship to get all custom values for the model.
     * Establishes a link between the main model (Product/Customer) 
     * and its custom stored values.
     */
    public function customFieldValues()
    {
        return $this->morphMany(CustomFieldValue::class, 'fieldable');
    }

    /**
     * Get all active field definitions for this specific model type.
     * Fetches the configuration (label, type, required) 
     * defined in the settings for this model.
     */
    public static function customFieldDefinitions()
    {
        return CustomField::where('model_type', static::class)
            ->where('is_active', true)
            ->orderBy('order', 'asc')
            ->get();
    }

    /**
     * Save or Update custom fields for the current model instance.
     * Iterates through the input array and updates 
     * existing values or creates new ones in the separate table.
     */
    public function saveCustomFields(array $fields)
    {
        foreach ($fields as $fieldId => $value) {
            if (is_null($value)) continue;

            // English: Convert array to string for multi-select/checkboxes
            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            $this->customFieldValues()->updateOrCreate(
                ['custom_field_id' => $fieldId],
                ['value' => $value]
            );
        }
    }

    /**
     * Get a specific custom field value by its slug/name.
     * Allows retrieving a specific custom value 
     * using the field's internal name (e.g., 'warranty_period').
     */
    public function getCustomValue($fieldName)
    {
        $field = CustomField::where('model_type', static::class)
            ->where('name', $fieldName)
            ->first();

        if (!$field) return null;

        return $this->customFieldValues()
            ->where('custom_field_id', $field->id)
            ->first()?->value;
    }

    /**
     * Auto-delete custom values when the main model is deleted.
     * Ensures data integrity by removing orphan 
     * custom values when a Product or Customer is removed.
     */
    protected static function bootHasCustomFields()
    {
        static::deleting(function ($model) {
            $model->customFieldValues()->delete();
        });
    }
}
