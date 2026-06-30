<?php

namespace App\Filament\Admin\Resources\Spks\Pages;

use App\Filament\Admin\Resources\Concerns\RefreshesListRecords;
use App\Filament\Admin\Resources\Concerns\UsesPolling;
use App\Filament\Admin\Resources\Spks\SpkResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListSpks extends ListRecords
{
    use RefreshesListRecords;
    use UsesPolling;

    protected static string $resource = SpkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'active' => Tab::make('Active')
                ->modifyQueryUsing(fn (Builder $query) => $query->withoutTrashed()),
            'trash' => Tab::make('Trash')
                ->modifyQueryUsing(fn (Builder $query) => $query->onlyTrashed()),
        ];
    }
}
