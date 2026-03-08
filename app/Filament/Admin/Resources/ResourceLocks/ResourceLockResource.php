<?php

namespace App\Filament\Admin\Resources\ResourceLocks;

use App\Filament\Admin\Resources\ResourceLocks\Pages\ListResourceLocks;
use App\Filament\Admin\Resources\ResourceLocks\Tables\ResourceLocksTable;
use App\Models\ResourceLock;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ResourceLockResource extends Resource
{
    protected static ?string $model = ResourceLock::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLockClosed;

    protected static string | UnitEnum | null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 100;

    public static function table(Table $table): Table
    {
        return ResourceLocksTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListResourceLocks::route('/'),
        ];
    }
}
