<?php

namespace App\Http\Controllers;

use App\DataTables\ActivityLogDataTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(ActivityLogDataTable $dataTable)
    {
        $events = Activity::distinct()->pluck('description')->toArray();

        // Get unique model classes and make them readable
        $targetModels = Activity::distinct()->pluck('subject_type')->mapWithKeys(function ($item) {
            if (!$item) return [];
            // Convert 'App\Models\Product' to 'Product'
            $name = class_basename($item); 
            return [$item => $name];
        })->toArray();

        $users = Activity::query()
            ->join('users', function($join) {
                $join->on(DB::raw('CAST(activity_log.causer_id AS BIGINT)'), '=', 'users.id')
                    ->where('activity_log.causer_type', '=', 'App\Models\User');
            })
            ->distinct()
            ->pluck('activity_log.causer_id', 'users.name')
            ->toArray();

        return $dataTable->render('backend.logs.activity_log', compact('events', 'users', 'targetModels'));
    }

    public function details($id)
    {
        $log = Activity::with('causer')->findOrFail($id);

        $type = strtolower($log->description);

        $attributes = $log->properties['attributes'] ?? [];
        $old = $log->properties['old'] ?? [];

        return response()->json([
            'id'          => $log->id,
            'user'        => optional($log->causer)->name ?? 'System',
            'action'      => ucfirst($log->description),
            'date'        => $log->created_at->format('d M, Y H:i A'),
            'attributes'  => $attributes,
            'old'         => $old,
            'type'        => $type
        ]);
    }

    public function clear()
    {
        try {
            Activity::truncate();
            return response()->json([
                'status' => 'success',
                'message' => 'Activity logs cleared successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong!',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
