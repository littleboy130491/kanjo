<?php

namespace App\Filament\Admin\Resources\Invoices\Actions;

use App\Models\Client;
use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class CreateInvoiceClientAction
{
    public static function make(): Action
    {
        return Action::make('create_client')
            ->label('Create Client')
            ->icon('heroicon-o-user-plus')
            ->color('success')
            ->visible(fn (Invoice $record): bool => blank($record->client_id))
            ->action(function (Invoice $record) {
                $client = Client::create([
                    'name' => $record->client_name,
                    'company' => $record->client_company,
                    'email' => $record->client_email,
                    'phone' => $record->client_phone,
                    'notes' => [],
                ]);

                $record->update(['client_id' => $client->id]);
                Notification::make()->title('Client created and linked.')->success()->send();
            });
    }
}
