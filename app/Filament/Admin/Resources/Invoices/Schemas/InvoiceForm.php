<?php

namespace App\Filament\Admin\Resources\Invoices\Schemas;

use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\Clients\Schemas\ClientForm;
use App\Models\Client;
use App\Models\Company;
use App\Models\Service;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use SolutionForest\FilamentTranslateField\Forms\Component\Translate;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Invoice')
                    ->tabs([
                        Tab::make('Document Info')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Document Numbering')
                                    ->schema([
                                        TextInput::make('document_number')
                                            ->label('Document Number')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->placeholder('Auto-generated'),
                                        TextInput::make('slug')
                                            ->label('Public Slug')
                                            ->placeholder('Auto-generated after save')
                                            ->helperText('Leave empty to auto-generate from ID + random token. Fill manually to disable auto-generation.')
                                            ->maxLength(255)
                                            ->unique(ignoreRecord: true)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (?string $state, callable $set) => $set('slug', filled($state) ? Str::slug($state) : null))
                                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Str::slug($state) : null),
                                        TextInput::make('document_number_suffix')
                                            ->label('Suffix')
                                            ->default('DP')
                                            ->maxLength(10)
                                            ->helperText('DP / LN / REN / REV'),
                                        Toggle::make('document_number_override')
                                            ->label('Override Document Number')
                                            ->helperText('Enable to manually set the full document number')
                                            ->live()
                                            ->default(false),
                                        TextInput::make('document_number_manual')
                                            ->label('Manual Document Number')
                                            ->maxLength(255)
                                            ->visible(fn (Get $get): bool => $get('document_number_override'))
                                            ->required(fn (Get $get): bool => $get('document_number_override')),
                                    ])
                                    ->columns(2),

                                Section::make('Document Details')
                                    ->schema([
                                        Select::make('company_id')
                                            ->label('Company')
                                            ->options(fn () => Company::pluck('brand_name', 'id'))
                                            ->default(fn () => Company::first()?->id)
                                            ->required()
                                            ->searchable(),
                                        Select::make('proposal_id')
                                            ->label('Proposal (Optional)')
                                            ->options(fn () => \App\Models\Proposal::pluck('document_number', 'id'))
                                            ->searchable()
                                            ->preload()
                                            ->placeholder('Not linked to a proposal')
                                            ->native(false),
                                        Select::make('status')
                                            ->options(DocumentStatus::class)
                                            ->enum(DocumentStatus::class)
                                            ->default(DocumentStatus::DRAFT)
                                            ->required(),
                                        Select::make('payment_status')
                                            ->options(PaymentStatus::class)
                                            ->enum(PaymentStatus::class)
                                            ->default(PaymentStatus::UNPAID)
                                            ->required(),
                                        DatePicker::make('issue_date')
                                            ->required()
                                            ->default(now()),
                                        DatePicker::make('due_date')
                                            ->required()
                                            ->default(now()->addDays(30)),
                                    ])
                                    ->columns(2),
                            ]),

                        Tab::make('Client Info')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Section::make('Select Client (Optional)')
                                    ->schema([
                                        Select::make('client_id')
                                            ->label('Load from Client Database')
                                            ->options(fn () => Client::orderBy('company')->pluck('company', 'id'))
                                            ->searchable()
                                            ->live()
                                            ->preload()
                                            ->nullable()
                                            ->helperText('Select a client to auto-fill the fields below. The data will be saved to this invoice, not linked.')
                                            ->createOptionUsing(function (array $data): int {
                                                $client = Client::create($data);
                                                return $client->getKey();
                                            })
                                            ->createOptionForm(schema: [
                                                ClientForm::getClientInformationSection(),
                                                ClientForm::getNotesSection(),
                                            ])
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if ($state) {
                                                    $client = Client::find($state);
                                                    if ($client) {
                                                        $set('client_company', $client->company);
                                                        $set('client_name', $client->name);
                                                        $set('client_email', $client->email);
                                                        $set('client_phone', $client->phone);
                                                    }
                                                }
                                            }),
                                    ]),
                                Section::make('Client Information')
                                    ->schema([
                                        TextInput::make('client_company')
                                            ->label('Company Name')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('client_name')
                                            ->label('Contact Person')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('client_email')
                                            ->label('Email')
                                            ->email()
                                            ->maxLength(255),
                                        TextInput::make('client_phone')
                                            ->label('Phone')
                                            ->tel()
                                            ->maxLength(255),
                                    ])
                                    ->columns(2),

                                Section::make('Related Service (Optional)')
                                    ->schema([
                                        Select::make('service_id')
                                            ->label('Link to Service')
                                            ->options(fn () => Service::with('client')
                                                ->get()
                                                ->mapWithKeys(fn ($service) => [
                                                    $service->id => $service->name . ' - ' . ($service->client?->company ?? 'No Client')
                                                ]))
                                            ->searchable()
                                            ->preload()
                                            ->nullable()
                                            ->helperText('Optional: Link this invoice to an existing service'),
                                    ]),
                            ]),

                        Tab::make('Financials')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Section::make('Invoice Items')
                                    ->schema([
                                        Translate::make()
                                            ->schema([
                                                Repeater::make('items')
                                                    ->schema([
                                                        TextInput::make('title')
                                                            ->required()
                                                            ->maxLength(255)
                                                            ->columnSpan(1),
                                                        TextInput::make('price')
                                                            ->required()
                                                            ->numeric()
                                                            ->inputMode('decimal')
                                                            ->columnSpan(1),
                                                        Textarea::make('description')
                                                            ->rows(2)
                                                            ->nullable()
                                                            ->columnSpanFull(),
                                                    ])
                                                    ->columns(2)
                                                    ->addable()
                                                    ->reorderable()
                                                    ->deletable()
                                                    ->default([]),
                                            ])
                                            ->locales(['en', 'id'])
                                            ->suffixLocaleLabel(),
                                    ]),

                                Section::make('Currency & Tax')
                                    ->schema([
                                        Select::make('currency')
                                            ->options([
                                                'IDR' => 'IDR - Indonesian Rupiah',
                                                'USD' => 'USD - US Dollar',
                                                'EUR' => 'EUR - Euro',
                                            ])
                                            ->default('IDR')
                                            ->live()
                                            ->required(),
                                        TextInput::make('tax_rate')
                                            ->label('Tax Rate (%)')
                                            ->numeric()
                                            ->inputMode('decimal')
                                            ->default(0)
                                            ->required()
                                            ->suffix('%'),
                                    ])
                                    ->columns(2),

                                Section::make('Totals')
                                    ->schema([
                                        TextInput::make('subtotal')
                                            ->readonly()
                                            ->default(0)
                                            ->prefix(fn(Get $get) => $get('currency'))
                                            ->placeholder('Auto-calculated'),
                                        TextInput::make('tax_amount')
                                            ->readonly()
                                            ->default(0)
                                            ->prefix(fn(Get $get) => $get('currency'))
                                            ->placeholder('Auto-calculated'),
                                        TextInput::make('total')
                                            ->readonly()
                                            ->default(0)
                                            ->prefix(fn(Get $get) => $get('currency'))
                                            ->placeholder('Auto-calculated'),
                                    ])
                                    ->columns(3),
                            ]),

                        Tab::make('Internal')
                            ->icon('heroicon-o-lock-closed')
                            ->schema([
                                Section::make('Access Credentials')
                                    ->schema([
                                        TextInput::make('access_username')
                                            ->label('Access Username')
                                            ->nullable()
                                            ->maxLength(255)
                                            ->helperText('Optional: Custom username for client access. Falls back to global credentials.'),
                                        TextInput::make('access_password')
                                            ->label('Access Password')
                                            ->nullable()
                                            ->maxLength(255)
                                            ->password()
                                            ->revealable()
                                            ->helperText('Optional: Custom password for client access. Falls back to global credentials.'),
                                    ])
                                    ->columns(2),

                                Section::make('Notes')
                                    ->schema([
                                        Repeater::make('notes')
                                            ->schema([
                                                Textarea::make('note')
                                                    ->label('Note')
                                                    ->rows(2)
                                                    ->columnSpanFull(),
                                            ])
                                            ->addable()
                                            ->reorderable()
                                            ->deletable()
                                            ->default([])
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }
}
