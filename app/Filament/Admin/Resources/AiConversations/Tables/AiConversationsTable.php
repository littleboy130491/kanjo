<?php

namespace App\Filament\Admin\Resources\AiConversations\Tables;

use App\Filament\Admin\Resources\AiConversations\AiConversationResource;
use App\Filament\Admin\Resources\Proposals\ProposalResource;
use App\Models\AiConversation;
use App\Models\Proposal;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AiConversationsTable
{
    public static function configure(Table $table, bool $withProposal = true): Table
    {
        $columns = [];

        if ($withProposal) {
            $columns[] = TextColumn::make('proposal.document_number')
                ->label('Proposal')
                ->state(fn (AiConversation $record): string => $record->proposal?->document_number ?: 'Proposal #'.$record->proposal_id)
                ->url(fn (AiConversation $record): ?string => $record->proposal && ! $record->proposal->trashed()
                    ? ProposalResource::getUrl('edit', ['record' => $record->proposal])
                    : null)
                ->openUrlInNewTab()
                ->searchable()
                ->copyable();
            $columns[] = TextColumn::make('proposal.client_company')
                ->label('Client')
                ->searchable()
                ->limit(30)
                ->default('-');
        }

        $columns[] = TextColumn::make('first_question')
            ->label('First Question')
            ->limit(60)
            ->tooltip(fn (AiConversation $record): ?string => $record->first_question)
            ->default('-')
            ->wrap();
        $columns[] = TextColumn::make('messages_count')
            ->label('Messages')
            ->numeric()
            ->sortable();
        $columns[] = TextColumn::make('failed_messages_count')
            ->label('Errors')
            ->badge()
            ->color(fn (?int $state): string => $state ? 'danger' : 'gray')
            ->toggleable();
        $columns[] = TextColumn::make('created_at')
            ->label('Started')
            ->dateTime('d M Y H:i')
            ->sortable();
        $columns[] = TextColumn::make('last_message_at')
            ->label('Last Message')
            ->since()
            ->dateTimeTooltip('d M Y H:i:s')
            ->sortable();
        $columns[] = TextColumn::make('locale')
            ->badge()
            ->formatStateUsing(fn (?string $state): string => strtoupper((string) $state))
            ->toggleable();
        $columns[] = TextColumn::make('ip_address')
            ->label('IP')
            ->searchable()
            ->copyable()
            ->default('-')
            ->toggleable();

        $filters = [];

        if ($withProposal) {
            $filters[] = SelectFilter::make('proposal_id')
                ->label('Proposal')
                ->relationship(
                    'proposal',
                    'document_number',
                    fn (Builder $query): Builder => $query->whereHas('aiConversations'),
                )
                ->getOptionLabelFromRecordUsing(fn (Proposal $record): string => self::proposalLabel($record))
                ->searchable(['document_number', 'client_company'])
                ->preload();
        }

        $filters[] = TernaryFilter::make('has_errors')
            ->label('Has Errors')
            ->queries(
                true: fn (Builder $query) => $query->whereHas('messages', fn (Builder $messages) => $messages->whereNotNull('error')),
                false: fn (Builder $query) => $query->whereDoesntHave('messages', fn (Builder $messages) => $messages->whereNotNull('error')),
            );
        $filters[] = Filter::make('created_at')
            ->label('Started')
            ->schema([
                DatePicker::make('from')
                    ->label('From'),
                DatePicker::make('until')
                    ->label('Until'),
            ])
            ->query(fn (Builder $query, array $data): Builder => $query
                ->when($data['from'] ?? null, fn (Builder $query, $date) => $query->whereDate('ai_conversations.created_at', '>=', $date))
                ->when($data['until'] ?? null, fn (Builder $query, $date) => $query->whereDate('ai_conversations.created_at', '<=', $date)));

        $configured = $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withSummary())
            ->columns($columns)
            ->defaultSort('created_at', 'desc')
            ->filters($filters)
            ->emptyStateHeading('No conversations yet')
            ->emptyStateDescription('Chats from visitors on published proposals will appear here.');

        if ($withProposal) {
            return $configured
                ->recordActions([
                    ViewAction::make(),
                    DeleteAction::make(),
                ])
                ->toolbarActions([
                    DeleteBulkAction::make(),
                ]);
        }

        return $configured
            ->recordUrl(fn (AiConversation $record): string => AiConversationResource::getUrl('view', ['record' => $record]))
            ->recordActions([])
            ->toolbarActions([]);
    }

    private static function proposalLabel(Proposal $proposal): string
    {
        return collect([
            $proposal->document_number ?: 'Proposal #'.$proposal->getKey(),
            $proposal->client_company,
        ])->filter()->implode(' — ');
    }
}
