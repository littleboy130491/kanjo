<?php

namespace App\Filament\Admin\Resources\Services\Actions;

use App\Models\Invoice;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

class UnlinkInvoiceServiceBulkAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('unlink_service')
            ->label('Unlink Service')
            ->icon('heroicon-o-link-slash')
            ->color('danger')
            ->requiresConfirmation()
            ->action(function (Collection $records): void {
                $records->each(fn (Invoice $record): bool => $record->update(['service_id' => null]));

                Notification::make()
                    ->title('Selected invoices unlinked from service.')
                    ->success()
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
