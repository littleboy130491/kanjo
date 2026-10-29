<?php

namespace App\Filament\Admin\Resources\AiConversations\Schemas;

use App\Filament\Admin\Resources\Proposals\ProposalResource;
use App\Models\AiConversation;
use App\Models\AiConversationMessage;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AiConversationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Conversation')
                    ->schema([
                        TextEntry::make('proposal.document_number')
                            ->label('Proposal')
                            ->state(fn (AiConversation $record): string => $record->proposal?->document_number ?: 'Proposal #'.$record->proposal_id)
                            ->url(fn (AiConversation $record): ?string => $record->proposal && ! $record->proposal->trashed()
                                ? ProposalResource::getUrl('edit', ['record' => $record->proposal])
                                : null)
                            ->openUrlInNewTab()
                            ->copyable()
                            ->default('-'),
                        TextEntry::make('proposal.client_company')
                            ->label('Client')
                            ->default('-'),
                        TextEntry::make('created_at')
                            ->label('Started')
                            ->dateTime('d M Y H:i:s'),
                        TextEntry::make('last_message_at')
                            ->label('Last Message')
                            ->dateTime('d M Y H:i:s')
                            ->default('-'),
                        TextEntry::make('messages_count')
                            ->label('Messages'),
                        TextEntry::make('locale')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => strtoupper((string) $state)),
                        TextEntry::make('ip_address')
                            ->label('IP')
                            ->default('-')
                            ->copyable(),
                        TextEntry::make('uuid')
                            ->label('Conversation ID')
                            ->copyable(),
                        TextEntry::make('user_agent')
                            ->label('User Agent')
                            ->default('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(4)
                    ->collapsible()
                    ->columnSpanFull(),

                Section::make('Transcript')
                    ->schema([
                        RepeatableEntry::make('messages')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('role')
                                    ->hiddenLabel()
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => $state === AiConversationMessage::ROLE_USER ? 'Visitor' : 'AI')
                                    ->color(fn (string $state, AiConversationMessage $record): string => match (true) {
                                        filled($record->error) => 'danger',
                                        $state === AiConversationMessage::ROLE_USER => 'info',
                                        default => 'success',
                                    }),
                                TextEntry::make('created_at')
                                    ->hiddenLabel()
                                    ->dateTime('d M Y H:i:s')
                                    ->color('gray'),
                                TextEntry::make('meta')
                                    ->hiddenLabel()
                                    ->state(fn (AiConversationMessage $record): ?string => self::meta($record))
                                    ->color('gray')
                                    ->alignEnd(),
                                TextEntry::make('content')
                                    ->hiddenLabel()
                                    ->markdown()
                                    ->columnSpanFull(),
                                TextEntry::make('error')
                                    ->label('Error')
                                    ->color('danger')
                                    ->visible(fn (AiConversationMessage $record): bool => filled($record->error))
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
                            ->contained(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private static function meta(AiConversationMessage $record): ?string
    {
        if ($record->role !== AiConversationMessage::ROLE_ASSISTANT) {
            return null;
        }

        $parts = array_filter([
            $record->provider,
            $record->model,
            $record->input_tokens || $record->output_tokens
                ? number_format((int) $record->input_tokens).' in / '.number_format((int) $record->output_tokens).' out tokens'
                : null,
        ]);

        return $parts === [] ? null : implode(' · ', $parts);
    }
}
