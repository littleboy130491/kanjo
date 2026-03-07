<?php

namespace App\Filament\Admin\Resources\Services\Pages;

use App\Filament\Admin\Resources\Services\ServiceResource;
use App\Filament\Admin\Resources\Services\Support\ServiceInvoiceSupport;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditService extends EditRecord
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ServiceInvoiceSupport::makeCreateRenewalInvoiceAction()
                ->visible(fn (): bool => filled($this->record->client_id))
                ->action(fn () => ServiceInvoiceSupport::executeCreateRenewalInvoiceAction($this->record)),
            DeleteAction::make(),
            ...parent::getHeaderActions(),
        ];
    }
}
