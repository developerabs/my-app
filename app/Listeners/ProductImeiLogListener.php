<?php

namespace App\Listeners;

use App\Events\ProductImeiEvent;
use App\Models\ProductImeiLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ProductImeiLogListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ProductImeiEvent $event): void
    {
        ProductImeiLog::create([
            'product_imei_id' => $event->imeiId,
            'branch_id'       => $event->branchId,
            'event_type'      => $event->eventType,
            'description'     => $event->description,
            'causable_type'   => 'App\Models\ProductImei',
            'causable_id'     => $event->imeiId,
            'user_id'         => auth()->id(),
        ]);
    }
}
