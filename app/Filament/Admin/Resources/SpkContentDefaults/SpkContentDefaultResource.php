<?php

namespace App\Filament\Admin\Resources\SpkContentDefaults;

use App\Filament\Admin\Resources\SpkContentDefaults\Pages\CreateSpkContentDefault;
use App\Filament\Admin\Resources\SpkContentDefaults\Pages\EditSpkContentDefault;
use App\Filament\Admin\Resources\SpkContentDefaults\Pages\ListSpkContentDefaults;
use App\Filament\Admin\Resources\SpkContentDefaults\Schemas\SpkContentDefaultForm;
use App\Filament\Admin\Resources\SpkContentDefaults\Tables\SpkContentDefaultsTable;
use App\Models\SpkContentDefault;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SpkContentDefaultResource extends Resource
{
    protected static ?string $model = SpkContentDefault::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'SPK Content Defaults';

    protected static ?string $modelLabel = 'SPK Content Default';

    protected static ?string $pluralModelLabel = 'SPK Content Defaults';

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return SpkContentDefaultForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SpkContentDefaultsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSpkContentDefaults::route('/'),
            'create' => CreateSpkContentDefault::route('/create'),
            'edit' => EditSpkContentDefault::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('field_key', SpkContentDefault::GLOBAL_FIELD_KEY);
    }
}
