<?php

namespace App\Filament\Admin\Resources\Users;

use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Admin\Resources\Users\Schemas\UserForm;
use App\Filament\Admin\Resources\Users\Tables\UsersTable;
use App\Enums\UserRole;
use App\Models\User;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user?->hasRole(UserRole::SuperAdmin->value)) {
            return $query;
        }

        return $query->whereKey($user?->getAuthIdentifier());
    }
}
