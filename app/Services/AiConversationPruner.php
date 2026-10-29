<?php

namespace App\Services;

use App\Models\AiConversation;
use App\Models\AiConversationMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AiConversationPruner
{
    public function pruneOlderThanDays(int $days): int
    {
        $cutoff = Carbon::now()->subDays($days);
        $deleted = 0;

        AiConversation::query()
            ->where(fn ($query) => $query
                ->where('last_message_at', '<=', $cutoff)
                ->orWhere(fn ($query) => $query
                    ->whereNull('last_message_at')
                    ->where('created_at', '<=', $cutoff)))
            ->select('id')
            ->chunkById(500, function ($conversations) use (&$deleted): void {
                $ids = $conversations->modelKeys();

                DB::transaction(function () use ($ids, &$deleted): void {
                    AiConversationMessage::query()->whereIn('ai_conversation_id', $ids)->delete();
                    $deleted += AiConversation::query()->whereKey($ids)->delete();
                });
            });

        return $deleted;
    }
}
