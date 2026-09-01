<?php

namespace App\Models\landlord;

use App\Traits\HasFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class LandlordMedia extends Model
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

    public function getUrlAttribute()
    {
        return asset('storage/' . $this->path);
    }

    public static function cleanupUnused()
    {
        $unused = self::where('used', false)->get();
        foreach ($unused as $media) {
            Storage::disk($media->disk)->delete($media->path);
            $media->delete();
        }
    }
}
