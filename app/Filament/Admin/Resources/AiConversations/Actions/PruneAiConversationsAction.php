<?php

namespace App\Filament\Admin\Resources\AiConversations\Actions;

use App\Filament\Admin\Resources\AiConversations\AiConversationResource;
use App\Services\AiConversationPruner;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class PruneAiConversationsAction
{
    public static function make(bool $redirectToIndexOnSuccess = false): Action
    {
        $action = Action::make('prune_ai_conversations')
            ->label('Prune Conversations')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->modalHeading('Prune AI Conversations')
            ->modalDescription('Delete conversations, and all their messages, whose last message is this many days old or older.')
            ->schema([
                TextInput::make('days')
                    ->label('Days')
                    ->numeric()
                    ->minValue(1)
                    ->default(90)
                    ->required(),
            ])
            ->action(function (array $data, AiConversationPruner $pruner): void {
                $days = max(1, (int) ($data['days'] ?? 90));
                $deletedCount = $pruner->pruneOlderThanDays($days);

                Notification::make()
                    ->title($deletedCount > 0
                        ? "Pruned {$deletedCount} conversation(s)."
                        : 'No conversations matched the prune rule.')
                    ->body("Deleted conversations {$days} day(s) old or older.")
                    ->success()
                    ->send();
            });

        if ($redirectToIndexOnSuccess) {
            $action->successRedirectUrl(AiConversationResource::getUrl('index'));
        }

        return $action;
    }
}
