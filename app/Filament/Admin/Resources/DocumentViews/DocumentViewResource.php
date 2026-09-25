<?php

namespace App\Filament\Admin\Resources\DocumentViews;

use App\Filament\Admin\Resources\DocumentViews\Pages\ListDocumentViews;
use App\Filament\Admin\Resources\DocumentViews\Pages\ViewDocumentView;
use App\Filament\Admin\Resources\DocumentViews\Schemas\DocumentViewInfolist;
use App\Filament\Admin\Resources\DocumentViews\Tables\DocumentViewsTable;
use App\Models\DocumentView;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DocumentViewResource extends Resource
{
    protected static ?string $model = DocumentView::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-eye';

    protected static string|UnitEnum|null $navigationGroup = 'Documents';

    protected static ?int $navigationSort = 35;

    public static function getNavigationLabel(): string
    {
        return 'Document Views';
    }

    public static function getModelLabel(): string
    {
        return 'Document View';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Document Views';
    }

    public static function infolist(Schema $schema): Schema
    {
        return DocumentViewInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentViewsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['viewable']);
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
            'index' => ListDocumentViews::route('/'),
            'view' => ViewDocumentView::route('/{record}'),
        ];
    }

    public static function resolveDocumentEditUrl(DocumentView $record): ?string
    {
        $resource = DocumentViewsTable::resolveDocumentResourceClass($record->viewable_type);

        if ($resource === null || $record->viewable === null) {
            return null;
        }

        return $resource::getUrl('edit', ['record' => $record->viewable]);
    }
}
