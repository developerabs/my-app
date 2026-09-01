<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache; // Added for clarity
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

trait HasFiles
{

    protected ?string $fileCacheTag = null;

    protected function getFileCacheTag(): string
    {
        if ($this->fileCacheTag === null) {
            $this->fileCacheTag = (function_exists('tenant') && tenant('id')) ? tenant_tag() : landlord_tag();
        }

        return $this->fileCacheTag;
    }

    // public function initializeHasFiles()
    // {
    //     $this->fileCacheTag = (function_exists('tenant') &&  tenant('id')) ? tenant_tag() : landlord_tag();
    // }
    /**
     * Advanced image processor for large scale apps (WebP, Resize, Optional Thumbnail).
     */
    public function processImage(UploadedFile|null $file, string $folder, array $options = [], ?string $oldPath = null, ?string $disk = 's3'): ?string
    {
        if (!$file) return $oldPath;

        $width = $options['width'] ?? 800;
        $quality = $options['quality'] ?? 80;
        $createThumbnail = $options['thumbnail'] ?? false;
        $thumbWidth = $options['thumb_width'] ?? 150;

        // Cleanup old files and their cache
        $this->deleteFile($oldPath, $disk);
        if ($createThumbnail) {
            $this->deleteFile($this->getThumbnailPath($oldPath), $disk);
        }

        $fileName = Str::random(40) . '.webp';
        $fullPath = rtrim($folder, '/') . '/' . $fileName;

        $manager = ImageManager::gd();
        $image = $manager->read($file);

        // Process Main Image
        $mainImage = clone $image;
        $mainImage->scale(width: $width);
        Storage::disk($disk)->put($fullPath, (string) $mainImage->toWebp($quality));

        // Process Thumbnail
        if ($createThumbnail) {
            $thumbPath = rtrim($folder, '/') . '/thumbs/' . $fileName;
            $thumbImage = clone $image;
            $thumbImage->scale(width: $thumbWidth);
            Storage::disk($disk)->put($thumbPath, (string) $thumbImage->toWebp(70));
        }

        return $fullPath;
    }

    /**
     * Helper to get thumbnail path.
     */
    public function getThumbnailPath(?string $path): ?string
    {
        if (!$path) return null;
        $directory = dirname($path);
        $fileName = basename($path);

        return ($directory === '.' || $directory === '/')
            ? "thumbs/{$fileName}"
            : "{$directory}/thumbs/{$fileName}";
    }

    /**
     * Handle image upload.
     */
    public function uploadFiles(Request $request, string $key = 'images', string $folder = 'uploads', ?string $disk = null): string|array|null
    {
        if (!$request->hasFile($key)) return null;
        return file_upload($request->file($key), $folder, $disk);
    }

    public function uploadUploadedFile(UploadedFile|array|null $files, string $folder = 'uploads', ?string $disk = null): string|array|null
    {
        if (!$files) return null;
        return file_upload($files, $folder, $disk);
    }

    /**
     * Handle image update.
     */
    public function updateFile(Request $request, string $key = 'image', ?string $oldPath = null, string $folder = 'uploads', ?string $disk = null): ?string
    {
        if (!$request->hasFile($key)) return $oldPath;

        // Delete old file cache before updating
        $this->clearFileCache($oldPath, $disk ?? 's3');

        return file_update($request->file($key), $oldPath, $folder, $disk);
    }

    /**
     * Handle image delete with cache clearing.
     */
    public function deleteFile(?string $path, ?string $disk = 's3'): void
    {
        if ($path) {
            $this->clearFileCache($path, $disk);
            file_delete($path, $disk);
        }
    }

    /**
     * Clear the cached URL for a specific file.
     */
    protected function clearFileCache(?string $path, ?string $disk): void
    {
        if ($path) {
            Cache::tags([$this->getFileCacheTag()])->forget('file_url_' . md5($path . ($disk ?? 's3')));
        }
    }

    /**
     * Get full image URL with 24-hour caching.
     */
    public function getFileUrl(?string $path, ?string $disk = 's3', ?string $default = null): string
    {
        if (!$path) return $default ?? url('images/preview_image.png');

        $cacheKey = 'file_url_' . md5($path . $disk);

        return Cache::tags([$this->fileCacheTag])->remember($cacheKey, now()->addDay(), function () use ($path, $disk) {
            return file_url($path, $disk);
        });
    }

    /**
     * Magic Accessor for _url properties.
     */
    public function __get($key)
    {
        if (str_ends_with($key, '_url')) {
            $column = str_replace('_url', '', $key);

            // Access attributes safely using Eloquent's internal method
            if (array_key_exists($column, $this->attributes)) {
                return $this->getFileUrl($this->attributes[$column], 's3');
            }
        }

        return parent::__get($key);
    }

    public function moveLargeFile(?string $fileId, string $folder, ?string $disk = 's3'): ?string
    {
        if (!$fileId) return null;

        $tempPath = "temp/{$fileId}";

        // ফাইলপন্ড থেকে আসা অরিজিনাল নাম বা র‍্যান্ডম নাম সেট করুন
        $fileName = Str::random(40) . '.zip'; // বা এক্সটেনশন ডাইনামিক করতে পারেন
        $finalPath = rtrim($folder, '/') . '/' . $fileName;

        if (Storage::disk($disk)->exists($tempPath)) {
            Storage::disk($disk)->move($tempPath, $finalPath);
            return $finalPath;
        }

        return null;
    }

    public function streamToS3(string $localTempPath, string $folder, ?string $originalName = null, ?string $disk = 's3'): ?string
    {
        if (!file_exists($localTempPath)) return null;

        // যদি অরিজিনাল নাম থাকে তবে সেটা থেকে এক্সটেনশন নিন, না থাকলে .zip ডিফল্ট
        $extension = $originalName ? pathinfo($originalName, PATHINFO_EXTENSION) : 'zip';

        $fileName = Str::random(40) . '.' . $extension;
        $finalPath = rtrim($folder, '/') . '/' . $fileName;

        $stream = fopen($localTempPath, 'r+');
        $status = Storage::disk($disk)->put($finalPath, $stream);

        if (is_resource($stream)) {
            fclose($stream);
        }

        if ($status) {
            unlink($localTempPath);
            return $finalPath;
        }

        return null;
    }
}
