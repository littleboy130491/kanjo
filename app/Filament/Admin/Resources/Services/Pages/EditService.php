<?php

namespace App\Filament\Admin\Resources\Services\Pages;

use App\Filament\Admin\Resources\Invoices\InvoiceResource;
use App\Filament\Admin\Resources\Services\ServiceResource;
use App\Filament\Admin\Resources\Services\Support\ServiceInvoiceSupport;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Throwable;

class EditService extends EditRecord
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_renewal_invoice')
                ->label('Create Renewal Invoice')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->visible(fn (): bool => filled($this->record->client_id))
                ->action(function () {
                    try {
                        $invoice = ServiceInvoiceSupport::createRenewalInvoice($this->record);

                        Notification::make()
                            ->title('Renewal invoice created.')
                            ->success()
                            ->send();

                        return redirect(InvoiceResource::getUrl('edit', ['record' => $invoice]));
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->title($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            ...parent::getHeaderActions(),
        ];
    }
}
