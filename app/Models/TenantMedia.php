<?php

namespace App\Models;

use App\Traits\HasFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TenantMedia extends Model
{
    use HasFiles;
    
    protected $fillable = [
        'path',
        'disk',
        'type',
        'original_name',
        'used',
        'model_type', 
        'model_id'
    ];

    public static function cleanupUnused()
    {
        $unused = self::where('used', false)->get();
        foreach ($unused as $media) {
            Storage::disk($media->disk)->delete($media->path);
            $media->delete();
        }
    }
}
