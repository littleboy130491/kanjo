<?php

namespace App\Filament\Admin\Resources\AiConversations\Pages;

use App\Filament\Admin\Resources\AiConversations\AiConversationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAiConversation extends ViewRecord
{
    protected static string $resource = AiConversationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
