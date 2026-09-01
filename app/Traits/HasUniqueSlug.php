<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasUniqueSlug
{
    /**
     * English: Generate a unique slug that checks both active and soft-deleted records.
     * This prevents integrity violations if you try to restore a deleted record.
     */
    public static function generateUniqueSlug(string $name, $currentId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        // English: Use static::withTrashed() to ensure global uniqueness across the table
        $query = static::withTrashed();

        while ($query->clone()
            ->where('slug', $slug)
            ->when($currentId, fn($q) => $q->where('id', '!=', $currentId))
            ->exists()
        ) {
            $slug = $originalSlug . '-' . $counter++;
        }

        return $slug;
    }
}