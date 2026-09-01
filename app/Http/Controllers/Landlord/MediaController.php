<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use App\Models\landlord\LandlordMedia;
use App\Traits\HasFiles;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    use HasFiles;

    public function uploadQuillImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $url = $this->uploadFiles($request, 'image', 'landlord/quill');

        if (!$url) {
            return response()->json(['error' => 'Upload failed'], 500);
        }

        $media = LandlordMedia::create([
            'path' => $url,
            'disk' => 'public',
            'type' => 'image',
            'used' => false
        ]);
        return response()->json(['url' => $media->path_url]);
    }
    //
}
