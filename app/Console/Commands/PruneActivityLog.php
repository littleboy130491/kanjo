<?php

namespace App\Console\Commands;

use App\Services\ActivityLogPruner;
use Illuminate\Console\Command;

class PruneActivityLog extends Command
{
    protected $signature = 'activity-log:prune {days=30 : Delete logs this many days old or older}';

    protected $description = 'Prune activity logs older than the provided number of days.';

    public function handle(ActivityLogPruner $pruner): int
    {
        $days = max(1, (int) $this->argument('days'));
        $deletedCount = $pruner->pruneOlderThanDays($days);

        $this->info("Deleted {$deletedCount} activity log record(s) that were {$days} day(s) old or older.");

        return self::SUCCESS;
    }
}
