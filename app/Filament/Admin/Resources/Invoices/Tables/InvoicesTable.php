<?php

namespace App\Filament\Admin\Resources\Invoices\Tables;

use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use App\Enums\ServiceStatus;
use App\Filament\Admin\Resources\Invoices\InvoiceResource;
use App\Filament\Admin\Resources\Proposals\ProposalResource;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Service;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client_company')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total')
                    ->money(fn ($record) => $record->currency)
                    ->sortable(),
                TextColumn::make('payment_status')
                    ->badge()
                    ->formatStateUsing(fn (PaymentStatus $state): string => $state->getLabel())
                    ->color(fn (PaymentStatus $state): string => $state->getColor())
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (DocumentStatus $state): string => $state->getLabel())
                    ->color(fn (DocumentStatus $state): string => $state->getColor())
                    ->sortable(),
                TextColumn::make('issue_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('proposal.document_number')
                    ->label('Proposal')
                    ->url(
                        fn ($record) => $record->proposal
                        ? route('filament.admin.resources.proposals.edit', $record->proposal)
                        : null
                    )
                    ->openUrlInNewTab()
                    ->placeholder('No proposal')
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
            ->filters([
                SelectFilter::make('status')
                    ->options(DocumentStatus::class),
                SelectFilter::make('payment_status')
                    ->options(PaymentStatus::class),
                SelectFilter::make('company_id')
                    ->label('Company')
                    ->relationship('company', 'brand_name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('has_proposal')
                    ->label('Has Proposal')
                    ->placeholder('All invoices')
                    ->trueLabel('With proposal')
                    ->falseLabel('Without proposal')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('proposal_id'),
                        false: fn ($query) => $query->whereNull('proposal_id'),
                    ),
                Filter::make('issue_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from'),
                        \Filament\Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn ($query, $date) => $query->whereDate('issue_date', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn ($query, $date) => $query->whereDate('issue_date', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                Action::make('duplicate_invoice')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function (Invoice $record) {
                        $duplicate = $record->replicate([
                            'document_number',
                            'document_number_raw',
                            'document_number_suffix',
                            'issue_month',
                            'issue_year',
                            'created_at',
                            'updated_at',
                            'deleted_at',
                        ]);

                        $duplicate->status = DocumentStatus::DRAFT;
                        $duplicate->payment_status = PaymentStatus::UNPAID;
                        $duplicate->paid_amount = 0;
                        $duplicate->paid_at = null;
                        $duplicate->payment_method = null;
                        $duplicate->document_number_override = false;
                        $duplicate->save();

                        return redirect(InvoiceResource::getUrl('edit', ['record' => $duplicate]));
                    }),
                Action::make('mark_as_paid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (Invoice $record): bool => in_array($record->payment_status, [PaymentStatus::UNPAID, PaymentStatus::PARTIALLY_PAID, PaymentStatus::OVERDUE], true))
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('paid_amount')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                        \Filament\Forms\Components\TextInput::make('payment_method')
                            ->required()
                            ->maxLength(255),
                        \Filament\Forms\Components\DateTimePicker::make('paid_at')
                            ->required()
                            ->default(now()),
                    ])
                    ->action(function (Invoice $record, array $data) {
                        $paidAmount = (float) $data['paid_amount'];

                        $record->update([
                            'paid_amount' => $paidAmount,
                            'payment_method' => $data['payment_method'],
                            'paid_at' => $data['paid_at'],
                            'payment_status' => $paidAmount >= (float) $record->total
                                ? PaymentStatus::PAID
                                : PaymentStatus::PARTIALLY_PAID,
                        ]);
                    }),
                Action::make('view_proposal')
                    ->label('View Proposal')
                    ->icon('heroicon-o-eye')
                    ->visible(fn (Invoice $record): bool => filled($record->proposal_id))
                    ->url(fn (Invoice $record): string => ProposalResource::getUrl('edit', ['record' => $record->proposal_id])),
                Action::make('create_client')
                    ->label('Create Client')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->visible(fn (Invoice $record): bool => blank($record->client_id))
                    ->action(function (Invoice $record) {
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
                Action::make('create_service')
                    ->label('Create Service')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->color('success')
                    ->visible(fn (Invoice $record): bool => filled($record->client_id) && blank($record->service_id))
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('domain')
                            ->maxLength(255),
                    ])
                    ->action(function (Invoice $record, array $data) {
                        $service = Service::create([
                            'name' => $data['name'],
                            'domain' => $data['domain'] ?? null,
                            'client_id' => $record->client_id,
                            'status' => ServiceStatus::ON_GOING,
                            'notes' => [],
                        ]);

                        $record->update(['service_id' => $service->id]);
                        Notification::make()->title('Service created and linked.')->success()->send();
                    }),
                Action::make('download_pdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Invoice $record): string => route('pdf.invoice', [
                        'slug' => str_replace('/', '-', $record->document_number),
                    ]))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
