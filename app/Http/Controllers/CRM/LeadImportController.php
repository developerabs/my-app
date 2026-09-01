<?php
namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessLeadImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LeadImportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx|max:10240',
        ]);

        $uploadedFile = $request->file('file');
        $importDisk = config('filesystems.default', 's3');
        
        if (!is_string($importDisk) || !config('filesystems.disks.' . $importDisk)) {
            $importDisk = 'local';
        }

        $filePath = $uploadedFile->store('imports', $importDisk);

        try {
            ProcessLeadImport::dispatch($filePath, auth()->id(), $importDisk);

            return response()->json([
                'status'  => 'success',
                'message' => 'Import started in background. You will be notified once completed!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Import Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}