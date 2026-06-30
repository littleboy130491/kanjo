<?php

namespace App\Filament\Admin\Resources\Spks;

use App\Filament\Admin\Resources\Spks\Pages\CreateSpk;
use App\Filament\Admin\Resources\Spks\Pages\EditSpk;
use App\Filament\Admin\Resources\Spks\Pages\ListSpks;
use App\Filament\Admin\Resources\Spks\Schemas\SpkForm;
use App\Filament\Admin\Resources\Spks\Tables\SpksTable;
use App\Models\Spk;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SpkResource extends Resource
{
    protected static ?string $model = Spk::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|UnitEnum|null $navigationGroup = 'Documents';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'SPK';

    protected static ?string $pluralModelLabel = 'SPKs';

    public static function form(Schema $schema): Schema
    {
        return SpkForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SpksTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['resourceLock.user']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSpks::route('/'),
            'create' => CreateSpk::route('/create'),
            'edit' => EditSpk::route('/{record}/edit'),
        ];
    }
}
