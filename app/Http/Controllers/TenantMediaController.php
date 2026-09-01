<?php

namespace App\Http\Controllers;

use App\Models\TenantMedia;
use App\Traits\HasFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TenantMediaController extends Controller
{
    use HasFiles;

    public function uploadQuillImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $url = $this->uploadFiles($request, 'image', 'quill');

        if (!$url) {
            return response()->json(['error' => 'Upload failed'], 500);
        }

        $media = TenantMedia::create([
            'path' => $url,
            'disk' => 'public',
            'type' => 'image',
            'used' => false
        ]);
        return response()->json(['url' => $media->path_url]);
    }

    public function largeFileStore(Request $request)
    {
        $fileId = str()->uuid()->toString();
        return response($fileId, 200)->header('Content-Type', 'text/plain');
    }

    // public function largeFileUpdate(Request $request, $id)
    // {
    //     $fileId = trim($id, '"');

    //     $offset = $request->header('Upload-Offset');
    //     $content = $request->getContent();

    //     $tempDir = storage_path('app/temp-uploads');
    //     if (!file_exists($tempDir)) {
    //         mkdir($tempDir, 0777, true);
    //     }

    //     $tempFile = $tempDir . '/' . $fileId;

    //     // ফাইল অ্যাপেন্ড করুন
    //     file_put_contents($tempFile, $content, FILE_APPEND);

    //     return response()->json(['status' => 'offset_received', 'offset' => $offset]);
    // }
    public function largeFileUpdate(Request $request, $id)
    {
        $fileId = trim($id, '"');
        $tempDir = storage_path('app/temp-uploads');
        $tempPath = $tempDir . DIRECTORY_SEPARATOR . $fileId;

        file_put_contents($tempPath, $request->getContent(), FILE_APPEND);

        $totalSize = (int) $request->header('Upload-Length');
        $currentSize = filesize($tempPath);

        if ($currentSize >= $totalSize) {
            // ফাইলপন্ড থেকে অরিজিনাল ফাইলের নাম নিন
            $originalName = $request->header('Upload-Name');

            set_time_limit(0);

            // ট্রেইটে অরিজিনাল নাম পাস করুন
            $s3Path = $this->streamToS3($tempPath, 'products/digital', $originalName, 's3');

            return response($s3Path, 200)->header('Content-Type', 'text/plain');
        }

        return response()->json(['status' => 'processing', 'offset' => $currentSize]);
    }
}
