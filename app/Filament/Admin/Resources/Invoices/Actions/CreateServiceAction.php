<?php

namespace App\Filament\Admin\Resources\Invoices\Actions;

use App\Filament\Admin\Resources\Invoices\Support\InvoiceServiceSupport;
use App\Filament\Admin\Resources\Services\ServiceResource;
use App\Models\Invoice;
use App\Models\Service;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class CreateServiceAction
{
    public static function make(
        bool $asLink = false,
        ?callable $recordResolver = null,
        bool $useCurrentDateDefaults = false,
        ?callable $afterLinked = null,
        bool $notify = true,
    ): Action {
        $action = Action::make('create_service')
            ->label('Create Service')
            ->icon('heroicon-o-wrench-screwdriver')
            ->color('success')
            ->visible(fn (Invoice $record): bool => filled($record->client_id) && blank($record->service_id))
            ->schema(InvoiceServiceSupport::createServiceFormSchema(
                recordResolver: $recordResolver,
                useCurrentDateDefaults: $useCurrentDateDefaults,
            ))
            ->action(function (Invoice $record, array $data) use ($afterLinked, $notify) {
                $service = InvoiceServiceSupport::createServiceFromInvoice($record, $data);

                $record->update(['service_id' => $service->id]);

                if ($afterLinked !== null) {
                    $afterLinked($record, $service);
                }

                if ($notify) {
                    Notification::make()->title('Service generated from invoice and linked.')->success()->send();
                }

                return redirect(ServiceResource::getUrl('edit', ['record' => $service]));
            });

        if ($asLink) {
            $action->link();
        }

        return $action;
    }
}
