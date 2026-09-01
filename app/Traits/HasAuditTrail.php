<?php

namespace App\Traits;

trait HasAuditTrail
{
    public static function bootHasAuditTrail()
    {
        static::creating(function ($model) {
            if (auth()->check() && !$model->created_by) {
                $model->created_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });

        // English: Tracking who soft-deleted the record
        static::deleting(function ($model) {
            if (auth()->check() && method_exists($model, 'isForceDeleting') && !$model->isForceDeleting()) {
                $model->updateQuietly(['deleted_by' => auth()->id()]);
            }
        });
    }
}