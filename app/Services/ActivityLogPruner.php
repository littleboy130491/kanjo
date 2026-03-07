<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;

class ActivityLogPruner
{
    public function pruneOlderThanDays(int $days): int
    {
        $cutoff = Carbon::now()->subDays($days);

        return Activity::query()
            ->where('created_at', '<=', $cutoff)
            ->delete();
    }
}
