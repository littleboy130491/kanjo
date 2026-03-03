<?php

namespace App\Filament\Admin\Resources\Proposals\Tables;

use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\Invoices\InvoiceResource;
use App\Filament\Admin\Resources\Proposals\ProposalResource;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Proposal;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProposalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document_number')
                    ->searchable()
                    ->sortable()
                    ->weight('font-bold'),
                TextColumn::make('client_company')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                TextColumn::make('client_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('offer_name_1')
                    ->sortable(),
                TextColumn::make('offer_1_price')
                    ->money(fn($record) => $record->currency)
                    ->sortable()
                    ->alignment('right'),
                BadgeColumn::make('status')
                    ->formatStateUsing(fn(DocumentStatus $state): string => $state->getLabel())
                    ->color(fn(DocumentStatus $state): string => $state->getColor()),
                TextColumn::make('issue_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('invoices_count')
                    ->label('Invoices')
                    ->counts('invoices')
                    ->badge()
                    ->color('primary')
                    ->alignment('center'),
                TextColumn::make('company.brand_name')
                    ->label('Company')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(DocumentStatus::class),
                SelectFilter::make('company_id')
                    ->label('Issuing Company')
                    ->relationship('company', 'brand_name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('has_invoice')
                    ->label('Has Invoice')
                    ->queries(
                        true: fn(Builder $query) => $query->whereHas('invoices'),
                        false: fn(Builder $query) => $query->whereDoesntHave('invoices'),
                    ),
                Filter::make('issue_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from'),
                        \Filament\Forms\Components\DatePicker::make('to'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn(Builder $query, $date): Builder => $query->whereDate('issue_date', '>=', $date),
                            )
                            ->when(
                                $data['to'] ?? null,
                                fn(Builder $query, $date): Builder => $query->whereDate('issue_date', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                Action::make('create_invoice')
                    ->label('Create Invoice')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('primary')
                    ->action(function (Proposal $record) {
                        $invoice = self::createInvoiceFromProposal(
                            $record,
                            (float) $record->offer_1_price,
                            (string) $record->offer_name_1,
                            'DP',
                        );

                        return redirect(InvoiceResource::getUrl('edit', ['record' => $invoice]));
                    }),
                Action::make('duplicate_proposal')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function (Proposal $record) {
                        $record->loadMissing('portfolios');

                        $duplicate = $record->replicate([
                            'document_number',
                            'slug',
                            'document_number_raw',
                            'issue_month',
                            'issue_year',
                            'invoices_count',
                            'created_at',
                            'updated_at',
                            'deleted_at',
                        ]);

                        $duplicate->status = DocumentStatus::DRAFT;
                        $duplicate->document_number_override = false;
                        $duplicate->save();
                        $duplicate->portfolios()->sync($record->portfolios->modelKeys());

                        return redirect(ProposalResource::getUrl('edit', ['record' => $duplicate]));
                    }),
                Action::make('create_client')
                    ->label('Create Client')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->visible(fn(Proposal $record): bool => blank($record->client_id))
                    ->action(function (Proposal $record) {
                        $client = Client::create([
                            'name' => $record->client_name,
                            'company' => $record->client_company,
                            'email' => $record->client_email,
                            'phone' => $record->client_phone,
                            'notes' => [],
                        ]);

                        $record->update(['client_id' => $client->id]);

                        Notification::make()->title('Client created and linked.')->success()->send();
                    }),
                Action::make('download_pdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn(Proposal $record): string => route('pdf.proposal', [
                        'slug' => $record->slug ?: str_replace('/', '-', $record->document_number),
                    ]))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('change_status')
                        ->label('Change Status')
                        ->icon('heroicon-o-pencil-square')
                        ->form([
                            Select::make('status')
                                ->label('Status')
                                ->options(collect(DocumentStatus::cases())->mapWithKeys(
                                    fn(DocumentStatus $status): array => [$status->value => $status->getLabel()]
                                )->all())
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $status = DocumentStatus::from((string) $data['status']);

                            $records->each(fn(Proposal $record): bool => $record->update(['status' => $status]));
                        }),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function createInvoiceFromProposal(
        Proposal $proposal,
        float $price,
        string $title,
        string $suffix = 'NEW',
    ): Invoice
    {
        $subtotal = $price;
        $taxAmount = $subtotal * (((float) $proposal->tax_rate) / 100);

        $invoice = new Invoice([
            'client_company' => $proposal->client_company,
            'client_name' => $proposal->client_name,
            'client_email' => $proposal->client_email,
            'client_phone' => $proposal->client_phone,
            'client_id' => $proposal->client_id,
            'company_id' => $proposal->company_id,
            'user_id' => auth()->id() ?? $proposal->user_id,
            'currency' => $proposal->currency,
            'tax_rate' => $proposal->tax_rate,
            'tax_amount' => $taxAmount,
            'subtotal' => $subtotal,
            'total' => $subtotal + $taxAmount,
            'access_username' => $proposal->access_username,
            'access_password' => $proposal->access_password,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'items' => [
                [
                    'title' => $title,
                    'price' => $price,
                    'description' => '',
                ],
            ],
            'status' => DocumentStatus::DRAFT,
            'payment_status' => PaymentStatus::UNPAID,
            'proposal_id' => $proposal->id,
            'notes' => [],
        ]);

        $invoice->documentNumberSuffix = $suffix;
        $invoice->save();

        return $invoice;
    }
}

