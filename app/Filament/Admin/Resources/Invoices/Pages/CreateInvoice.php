<?php

namespace App\Filament\Admin\Resources\Invoices\Pages;

use App\Filament\Admin\Concerns\HasTranslatableRepeaterSync;
use App\Filament\Admin\Resources\Invoices\InvoiceResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    use HasTranslatableRepeaterSync;

    protected static string $resource = InvoiceResource::class;

    protected function getTranslatableRepeaterFieldKeys(): array
    {
        return ['items'];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Create')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->action('create'),
        ];
    }
}
