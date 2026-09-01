<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

use function Symfony\Component\String\u;

if (!function_exists('file_upload')) {
    function file_upload(UploadedFile|array $files, string $folder = 'uploads', ?string $disk = null): string|array|null
    {
        // ডিফল্ট ডিস্ক নির্ধারণ (সরাসরি কনফিগ থেকে নেওয়া ভালো)
        $disk = $disk ?? config('filesystems.default', 's3');
        $folder = trim($folder, '/');

        if (is_array($files)) {
            $paths = [];
            foreach ($files as $file) {
                // storePublicly ব্যবহার করলে visibility public অটোমেটিক সেট হয়
                $paths[] = $file->storePublicly($folder, ['disk' => $disk]);
            }
            return $paths;
        }

        return $files->storePublicly($folder, ['disk' => $disk]);
    }
}

if (!function_exists('file_delete')) {
    /**
     * Delete an image from storage.
     *
     * @param  string|null  $path
     * @param  string|null  $disk
     * @return void
     */
    function file_delete(?string $path, ?string $disk = null): void
    {
        if (!$path) return;

        $disk = $disk ?? 's3';

        if (Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }
}

if (!function_exists('file_update')) {
    /**
     * Replace old image with a new one.
     *
     * @param  UploadedFile  $newFile
     * @param  string|null  $oldPath
     * @param  string  $folder
     * @param  string|null  $disk
     * @return string
     */
    function file_update(UploadedFile $newFile, ?string $oldPath, string $folder = 'uploads', ?string $disk = null): string
    {
        file_delete($oldPath, $disk);
        return file_upload($newFile, $folder, $disk);
    }
}

if (!function_exists('file_url')) {
    function file_url(?string $path, ?string $disk = null, ?string $default = null): string
    {
        if (!$path) {
            return $default ? asset($default) : url('images/preview_image.png');
        }

        $disk = $disk ?? config('filesystems.default', 'public');

        try {
            // S3 বা লোকাল যেটাই হোক, সরাসরি URL জেনারেট হবে (কোনো ডিস্ক চেক নেই)
            return Storage::disk($disk)->url($path);
        } catch (\Exception $e) {
            return $default ? asset($default) : url('images/preview_image.png');
        }
    }
}