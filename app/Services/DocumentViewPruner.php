<?php

namespace App\Services;

use App\Models\DocumentView;
use Illuminate\Support\Carbon;

class DocumentViewPruner
{
    public function pruneOlderThanDays(int $days): int
    {
        $cutoff = Carbon::now()->subDays($days);

        return DocumentView::query()
            ->where('viewed_at', '<=', $cutoff)
            ->delete();
    }
}
