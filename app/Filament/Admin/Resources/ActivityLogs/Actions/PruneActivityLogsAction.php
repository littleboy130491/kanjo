<?php

namespace App\Filament\Admin\Resources\ActivityLogs\Actions;

use App\Filament\Admin\Resources\ActivityLogs\ActivityLogResource;
use App\Services\ActivityLogPruner;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class PruneActivityLogsAction
{
    public static function make(bool $redirectToIndexOnSuccess = false): Action
    {
        $action = Action::make('prune_activity_logs')
            ->label('Prune Logs')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->modalHeading('Prune Activity Logs')
            ->modalDescription('Delete activity log records that are this many days old or older.')
            ->schema([
                TextInput::make('days')
                    ->label('Days')
                    ->numeric()
                    ->minValue(1)
                    ->default(30)
                    ->required(),
            ])
            ->action(function (array $data, ActivityLogPruner $pruner): void {
                $days = max(1, (int) ($data['days'] ?? 30));
                $deletedCount = $pruner->pruneOlderThanDays($days);

                Notification::make()
                    ->title($deletedCount > 0
                        ? "Pruned {$deletedCount} activity log record(s)."
                        : 'No activity log records matched the prune rule.')
                    ->body("Deleted records {$days} day(s) old or older.")
                    ->success()
                    ->send();
            });

        if ($redirectToIndexOnSuccess) {
            $action->successRedirectUrl(ActivityLogResource::getUrl('index'));
        }

        return $action;
    }
}
