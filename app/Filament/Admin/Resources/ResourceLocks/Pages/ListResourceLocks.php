<?php

namespace App\Filament\Admin\Resources\ResourceLocks\Pages;

use App\Filament\Admin\Resources\ResourceLocks\ResourceLockResource;
use Filament\Resources\Pages\ListRecords;

class ListResourceLocks extends ListRecords
{
    protected static string $resource = ResourceLockResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
