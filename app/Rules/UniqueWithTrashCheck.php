<?php

namespace App\Rules;

use App\Models\Trash;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueWithTrashCheck implements ValidationRule
{

    protected $model;
    protected $column;
    protected $ignoreId;

    public function __construct($model, $column = 'code', $ignoreId = null)
    {
        $this->model = $model;
        $this->column = $column;
        $this->ignoreId = $ignoreId;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        $modelClass = $this->model;

        // ১. একটিভ রেকর্ডে চেক (বর্তমান আইডি বাদ দিয়ে)
        $activeExists = $modelClass::where($this->column, $value)
            ->when($this->ignoreId, function ($query) {
                return $query->where('id', '!=', $this->ignoreId);
            })
            ->exists();

        if ($activeExists) {
            $fail("The {$attribute} has already been taken.");
            return;
        }

        // ২. ট্র্যাশে চেক (ট্র্যাশে থাকা ডাটার আইডি যদি বর্তমান আইডির সমান না হয়)
        $trashed = $modelClass::onlyTrashed()
            ->where($this->column, $value)
            ->when($this->ignoreId, function ($query) {
                return $query->where('id', '!=', $this->ignoreId);
            })
            ->first();

        if ($trashed) {
            if (auth()->user()->can('manage_trash')) {
                $trashId = Trash::where('trashable_type', $modelClass)->where('trashable_id', $trashed->id)->value('id');

                $fail(json_encode([
                    'is_trashed' => true,
                    'id' => $trashId,
                    'name' => $trashed->name,
                    'message' => "This {$attribute} is already in trash. Do you want to restore it?"
                ]));
            } else {
                $fail("This {$attribute} is in trash and cannot be used.");
            }
        }
    }
}
