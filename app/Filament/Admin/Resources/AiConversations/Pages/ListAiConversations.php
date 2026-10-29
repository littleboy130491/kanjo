<?php

namespace App\Filament\Admin\Resources\AiConversations\Pages;

use App\Filament\Admin\Resources\AiConversations\Actions\PruneAiConversationsAction;
use App\Filament\Admin\Resources\AiConversations\AiConversationResource;
use Filament\Resources\Pages\ListRecords;

class ListAiConversations extends ListRecords
{
    protected static string $resource = AiConversationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PruneAiConversationsAction::make(),
        ];
    }
}
