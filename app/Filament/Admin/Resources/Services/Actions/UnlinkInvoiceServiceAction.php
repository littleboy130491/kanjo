<?php

namespace App\Filament\Admin\Resources\Services\Actions;

use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class UnlinkInvoiceServiceAction
{
    public static function make(): Action
    {
        return Action::make('unlink_service')
            ->label('Unlink Service')
            ->icon('heroicon-o-link-slash')
            ->color('danger')
            ->requiresConfirmation()
            ->action(function (Invoice $record): void {
                $record->update(['service_id' => null]);

                Notification::make()
                    ->title('Invoice unlinked from service.')
                    ->success()
                    ->send();
            });
    }
}
