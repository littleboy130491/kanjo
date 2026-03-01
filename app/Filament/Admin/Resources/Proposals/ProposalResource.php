<?php

namespace App\Filament\Admin\Resources\Proposals;

use App\Filament\Admin\Resources\Proposals\Pages\CreateProposal;
use App\Filament\Admin\Resources\Proposals\Pages\EditProposal;
use App\Filament\Admin\Resources\Proposals\Pages\ListProposals;
use App\Filament\Admin\Resources\Proposals\RelationManagers\InvoicesRelationManager;
use App\Filament\Admin\Resources\Proposals\Schemas\ProposalForm;
use App\Filament\Admin\Resources\Proposals\Tables\ProposalsTable;
use App\Models\Proposal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class ProposalResource extends Resource
{
    protected static ?string $model = Proposal::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|UnitEnum|null $navigationGroup = 'Documents';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return ProposalForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProposalsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            InvoicesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProposals::route('/'),
            'create' => CreateProposal::route('/create'),
            'edit' => EditProposal::route('/{record}/edit'),
        ];
    }
}
