<?php

namespace App\Filament\Admin\Resources\Proposals\Pages;

use App\Filament\Admin\Resources\Concerns\UsesResourceLock;
use App\Filament\Admin\Resources\Proposals\Actions\CreateInvoiceAction;
use App\Filament\Admin\Resources\Proposals\Actions\DownloadProposalPdfAction;
use App\Filament\Admin\Resources\Proposals\Actions\DuplicateProposalAction;
use App\Filament\Admin\Resources\Proposals\Actions\ViewProposalDocumentAction;
use App\Filament\Admin\Resources\Proposals\ProposalResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditProposal extends EditRecord
{
    use UsesResourceLock;

    protected static string $resource = ProposalResource::class;

    protected function getHeaderActions(): array
    {
        return $this->mergeLockActions([
            Action::make('save')
                ->label('Save')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->link()
                ->action('save'),
            DuplicateProposalAction::make(name: 'duplicate', asLink: true),
            CreateInvoiceAction::make(asLink: true),
            ViewProposalDocumentAction::make(asLink: true),
            DownloadProposalPdfAction::make(name: 'create_pdf', label: 'Create PDF', asLink: true)
                ->icon('heroicon-o-document-arrow-down'),
        ]);
    }
}
