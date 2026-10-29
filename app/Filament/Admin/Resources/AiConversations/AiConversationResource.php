<?php

namespace App\Filament\Admin\Resources\AiConversations;

use App\Filament\Admin\Resources\AiConversations\Pages\ListAiConversations;
use App\Filament\Admin\Resources\AiConversations\Pages\ViewAiConversation;
use App\Filament\Admin\Resources\AiConversations\Schemas\AiConversationInfolist;
use App\Filament\Admin\Resources\AiConversations\Tables\AiConversationsTable;
use App\Models\AiConversation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AiConversationResource extends Resource
{
    protected static ?string $model = AiConversation::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string|UnitEnum|null $navigationGroup = 'Documents';

    protected static ?int $navigationSort = 36;

    public static function getNavigationLabel(): string
    {
        return 'AI Conversations';
    }

    public static function getModelLabel(): string
    {
        return 'AI Conversation';
    }

    public static function getPluralModelLabel(): string
    {
        return 'AI Conversations';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return AiConversationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AiConversationsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['proposal']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAiConversations::route('/'),
            'view' => ViewAiConversation::route('/{record}'),
        ];
    }
}
