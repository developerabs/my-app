<?php

namespace App\Traits;

use App\Models\Trash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

trait HasTrash
{
    protected static function bootHasTrash(): void
    {
        static::softDeleted(function ($model) {
            if (Schema::hasTable('trashes')) {
                
                // Smart Polymorphic Name Resolver
                $trashName = method_exists($model, 'getTrashName') 
                    ? $model->getTrashName() 
                    : ($model->name 
                        ?? $model->title 
                        ?? $model->account_name 
                        ?? $model->expense_no 
                        ?? $model->voucher_no 
                        ?? $model->asset_name 
                        ?? $model->register_no 
                        ?? (class_basename($model) . ' #' . $model->id));

                Trash::updateOrCreate(
                    [
                        'trashable_type' => get_class($model),
                        'trashable_id'   => (string) $model->id,
                    ],
                    [
                        'name'       => $trashName,
                        'deleted_by' => Auth::id(),
                    ]
                );
            }
        });

        // When the model is restored from soft-delete
        static::restored(function ($model) {
            Trash::where('trashable_type', get_class($model))
                ->where('trashable_id', (string) $model->id)
                ->delete();
        });
    }

    /**
     * Optional: Polymorphic relation back to the Trash entry.
     */
    public function trashEntry()
    {
        return $this->morphOne(Trash::class, 'trashable');
    }
}