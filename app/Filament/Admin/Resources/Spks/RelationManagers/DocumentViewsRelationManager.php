<?php

namespace App\Filament\Admin\Resources\Spks\RelationManagers;

use App\Filament\Admin\Resources\DocumentViews\Tables\DocumentViewsTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class DocumentViewsRelationManager extends RelationManager
{
    protected static string $relationship = 'documentViews';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return DocumentViewsTable::configure($table, withDocument: false);
    }
}
