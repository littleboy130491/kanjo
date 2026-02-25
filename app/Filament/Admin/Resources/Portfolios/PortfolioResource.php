<?php

namespace App\Filament\Admin\Resources\Portfolios;

use App\Filament\Admin\Resources\Portfolios\Pages\CreatePortfolio;
use App\Filament\Admin\Resources\Portfolios\Pages\EditPortfolio;
use App\Filament\Admin\Resources\Portfolios\Pages\ListPortfolios;
use App\Filament\Admin\Resources\Portfolios\Schemas\PortfolioForm;
use App\Filament\Admin\Resources\Portfolios\Tables\PortfoliosTable;
use App\Models\Portfolio;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class PortfolioResource extends Resource
{
    protected static ?string $model = Portfolio::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static string|UnitEnum|null $navigationGroup = 'Data';

    public static function form(Schema $schema): Schema
    {
        return PortfolioForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PortfoliosTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPortfolios::route('/'),
            'create' => CreatePortfolio::route('/create'),
            'edit' => EditPortfolio::route('/{record}/edit'),
        ];
    }
}