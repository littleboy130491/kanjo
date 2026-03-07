<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Enums\UserRole;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (User $record): bool => auth()->user()?->hasRole(UserRole::SuperAdmin->value) === true
                    && auth()->id() !== $record->getKey())
                ->authorize(fn (User $record): bool => auth()->user()?->hasRole(UserRole::SuperAdmin->value) === true
                    && auth()->id() !== $record->getKey()),
        ];
    }
}
