<?php

namespace App\Traits;

use Spatie\Activitylog\Models\Activity;

trait LogIP
{
    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->properties = $activity->properties->merge([
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
