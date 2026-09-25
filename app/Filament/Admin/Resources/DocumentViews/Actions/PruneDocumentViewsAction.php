<?php

namespace App\Filament\Admin\Resources\DocumentViews\Actions;

use App\Filament\Admin\Resources\DocumentViews\DocumentViewResource;
use App\Services\DocumentViewPruner;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class PruneDocumentViewsAction
{
    public static function make(bool $redirectToIndexOnSuccess = false): Action
    {
        $action = Action::make('prune_document_views')
            ->label('Prune Views')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->modalHeading('Prune Document Views')
            ->modalDescription('Delete document view records that are this many days old or older.')
            ->schema([
                TextInput::make('days')
                    ->label('Days')
                    ->numeric()
                    ->minValue(1)
                    ->default(90)
                    ->required(),
            ])
            ->action(function (array $data, DocumentViewPruner $pruner): void {
                $days = max(1, (int) ($data['days'] ?? 90));
                $deletedCount = $pruner->pruneOlderThanDays($days);

                Notification::make()
                    ->title($deletedCount > 0
                        ? "Pruned {$deletedCount} document view record(s)."
                        : 'No document view records matched the prune rule.')
                    ->body("Deleted records {$days} day(s) old or older.")
                    ->success()
                    ->send();
            });

        if ($redirectToIndexOnSuccess) {
            $action->successRedirectUrl(DocumentViewResource::getUrl('index'));
        }

        return $action;
    }
}
