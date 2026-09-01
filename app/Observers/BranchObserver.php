<?php

namespace App\Observers;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BranchObserver
{
    /**
     * Handle the Branch "created" event.
     */
    public function created(Branch $branch): void
    {
        $superadmins = User::role('Super Admin')->get();

        if ($superadmins->isNotEmpty()) {
            $dataToInsert = [];
            foreach ($superadmins as $superadmin) {
                $dataToInsert[] = [
                    'user_id' => $superadmin->id,
                    'branch_id' => $branch->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('user_branches')->insert($dataToInsert);
        }
    }

    /**
     * Handle the Branch "updated" event.
     */
    public function updated(Branch $branch): void
    {
        //
    }

    /**
     * Handle the Branch "deleted" event.
     */
    public function deleted(Branch $branch): void
    {
        //
    }

    /**
     * Handle the Branch "restored" event.
     */
    public function restored(Branch $branch): void
    {
        //
    }

    /**
     * Handle the Branch "force deleted" event.
     */
    public function forceDeleted(Branch $branch): void
    {
        //
    }
}
