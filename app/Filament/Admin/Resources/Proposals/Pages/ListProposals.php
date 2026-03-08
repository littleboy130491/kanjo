<?php

namespace App\Filament\Admin\Resources\Proposals\Pages;

use App\Filament\Admin\Resources\Concerns\RefreshesListRecords;
use App\Filament\Admin\Resources\Proposals\ProposalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListProposals extends ListRecords
{
    use RefreshesListRecords;

    protected static string $resource = ProposalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'active' => Tab::make('Active'),
            'trash' => Tab::make('Trash')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->onlyTrashed()),
        ];
    }
}
