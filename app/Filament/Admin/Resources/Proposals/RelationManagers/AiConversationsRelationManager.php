<?php

namespace App\Filament\Admin\Resources\Proposals\RelationManagers;

use App\Filament\Admin\Resources\AiConversations\Tables\AiConversationsTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class AiConversationsRelationManager extends RelationManager
{
    protected static string $relationship = 'aiConversations';

    protected static ?string $title = 'AI Conversations';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return AiConversationsTable::configure($table, withProposal: false);
    }
}
