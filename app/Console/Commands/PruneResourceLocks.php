<?php

namespace App\Console\Commands;

use App\Models\ResourceLock;
use Illuminate\Console\Command;

class PruneResourceLocks extends Command
{
    protected $signature = 'resource-locks:prune';

    protected $description = 'Delete expired resource lock records.';

    public function handle(): int
    {
        $deletedCount = ResourceLock::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->delete();

        $this->info("Deleted {$deletedCount} expired resource lock(s).");

        return self::SUCCESS;
    }
}
