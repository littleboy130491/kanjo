<?php

namespace App\Filament\Admin\Resources\Spks\Pages;

use App\Filament\Admin\Resources\Concerns\UsesResourceLock;
use App\Filament\Admin\Resources\Spks\Actions\DuplicateSpkAction;
use App\Filament\Admin\Resources\Spks\Actions\ViewProposalAction;
use App\Filament\Admin\Resources\Spks\SpkResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditSpk extends EditRecord
{
    use UsesResourceLock;

    protected static string $resource = SpkResource::class;

    protected function getHeaderActions(): array
    {
        return $this->mergeLockActions([
            Action::make('save')
                ->label('Save')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->link()
                ->action('save'),
            DuplicateSpkAction::make(name: 'duplicate', asLink: true),
            ViewProposalAction::make(asLink: true),
        ]);
    }
}
