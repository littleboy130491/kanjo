<?php

namespace App\Filament\Admin\Resources\ProposalContentDefaults;

use App\Filament\Admin\Resources\ProposalContentDefaults\Pages\CreateProposalContentDefault;
use App\Filament\Admin\Resources\ProposalContentDefaults\Pages\EditProposalContentDefault;
use App\Filament\Admin\Resources\ProposalContentDefaults\Pages\ListProposalContentDefaults;
use App\Filament\Admin\Resources\ProposalContentDefaults\Schemas\ProposalContentDefaultForm;
use App\Filament\Admin\Resources\ProposalContentDefaults\Tables\ProposalContentDefaultsTable;
use App\Models\ProposalContentDefault;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProposalContentDefaultResource extends Resource
{
    protected static ?string $model = ProposalContentDefault::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Proposal Content Defaults';

    protected static ?string $modelLabel = 'Proposal Content Default';

    protected static ?string $pluralModelLabel = 'Proposal Content Defaults';

    public static function form(Schema $schema): Schema
    {
        return ProposalContentDefaultForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProposalContentDefaultsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProposalContentDefaults::route('/'),
            'create' => CreateProposalContentDefault::route('/create'),
            'edit' => EditProposalContentDefault::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->orderByDesc('is_default')->orderBy('name');
    }
}
