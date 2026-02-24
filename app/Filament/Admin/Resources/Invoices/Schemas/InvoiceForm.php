<?php

namespace App\Filament\Admin\Resources\Invoices\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
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
                                            ->readonly()
                                            ->placeholder('Auto-generated')
                                            ->columnSpan(2),
                                        TextInput::make('document_number_suffix')
                                            ->label('Suffix')
                                            ->maxLength(50)
                                            ->fullWidth(),
                                        Toggle::make('document_number_override')
                                            ->label('Override Document Number')
                                            ->helperText('Enable to manually enter custom document number suffix')
                                            ->default(false),
                                    ])
                                    ->columns(3),

                                Section::make('Document Details')
                                    ->schema([
                                        Select::make('company_id')
                                            ->label('Company')
                                            ->relationship('company', 'brand_name')
                                            ->required()
                                            ->searchable()
                                            ->preload(),
                                        Select::make('proposal_id')
                                            ->label('Proposal (Optional)')
                                            ->relationship('proposal', 'document_number')
                                            ->searchable()
                                            ->preload()
                                            ->placeholder('Not linked to a proposal')
                                            ->native(false),
                                        Select::make('status')
                                            ->options([
                                                'draft' => 'Draft',
                                                'published' => 'Published',
                                            ])
                                            ->default('draft')
                                            ->required(),
                                        Select::make('payment_status')
                                            ->options([
                                                'unpaid' => 'Unpaid',
                                                'partially_paid' => 'Partially Paid',
                                                'paid' => 'Paid',
                                                'overdue' => 'Overdue',
                                                'cancelled' => 'Cancelled',
                                            ])
                                            ->default('unpaid')
                                            ->required(),
                                        DatePicker::make('issue_date')
                                            ->required()
                                            ->default(now()),
                                        DatePicker::make('due_date')
                                            ->required(),
                                    ])
                                    ->columns(2),
                            ]),

                        Tab::make('Client Info')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Section::make('Client Information')
                                    ->schema([
                                        TextInput::make('client_company')
                                            ->label('Company Name')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('client_name')
                                            ->label('Contact Name')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('client_email')
                                            ->label('Email')
                                            ->email()
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->columns(2),
                            ]),

                        Tab::make('Items')
                            ->icon('heroicon-o-list-bullet')
                            ->schema([
                                Section::make('Invoice Items')
                                    ->schema([
                                        Translate::make()
                                            ->schema([
                                                Repeater::make('items')
                                                    ->schema([
                                                        TextInput::make('title')
                                                            ->required()
                                                            ->maxLength(255),
                                                        Textarea::make('description')
                                                            ->rows(2)
                                                            ->nullable(),
                                                        TextInput::make('price')
                                                            ->required()
                                                            ->numeric()
                                                            ->inputMode('decimal')
                                                            ->prefix(fn ($state, $get) => $get('../../currency') ?? 'IDR'),
                                                    ])
                                                    ->columns(3)
                                                    ->addable()
                                                    ->reorderable()
                                                    ->deletable()
                                                    ->default([]),
                                            ])
                                            ->locales(['en', 'id'])
                                            ->suffixLocaleLabel(),
                                    ]),
                            ]),

                        Tab::make('Financials')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Section::make('Currency & Tax')
                                    ->schema([
                                        Select::make('currency')
                                            ->options([
                                                'IDR' => 'IDR - Indonesian Rupiah',
                                                'USD' => 'USD - US Dollar',
                                                'EUR' => 'EUR - Euro',
                                            ])
                                            ->default('IDR')
                                            ->required(),
                                        TextInput::make('tax_rate')
                                            ->label('Tax Rate (%)')
                                            ->numeric()
                                            ->inputMode('decimal')
                                            ->default(0)
                                            ->suffix('%'),
                                    ])
                                    ->columns(2),

                                Section::make('Totals')
                                    ->schema([
                                        TextInput::make('subtotal')
                                            ->readonly()
                                            ->prefix(fn ($state, $get) => $get('currency') ?? 'IDR')
                                            ->placeholder('Auto-calculated'),
                                        TextInput::make('tax_amount')
                                            ->readonly()
                                            ->prefix(fn ($state, $get) => $get('currency') ?? 'IDR')
                                            ->placeholder('Auto-calculated'),
                                        TextInput::make('total')
                                            ->readonly()
                                            ->prefix(fn ($state, $get) => $get('currency') ?? 'IDR')
                                            ->placeholder('Auto-calculated'),
                                    ])
                                    ->columns(3),
                            ]),

                        Tab::make('Payment')
                            ->icon('heroicon-o-credit-card')
                            ->schema([
                                Section::make('Payment Details')
                                    ->schema([
                                        TextInput::make('paid_amount')
                                            ->label('Paid Amount')
                                            ->numeric()
                                            ->inputMode('decimal')
                                            ->default(0)
                                            ->prefix(fn ($state, $get) => $get('currency') ?? 'IDR'),
                                        Select::make('payment_method')
                                            ->options([
                                                'bank_transfer' => 'Bank Transfer',
                                                'cash' => 'Cash',
                                                'credit_card' => 'Credit Card',
                                                'debit_card' => 'Debit Card',
                                                'check' => 'Check',
                                                'paypal' => 'PayPal',
                                                'other' => 'Other',
                                            ])
                                            ->nullable()
                                            ->placeholder('Select payment method'),
                                        DatePicker::make('paid_at')
                                            ->label('Paid At')
                                            ->nullable()
                                            ->placeholder('Not yet paid'),
                                    ])
                                    ->columns(2),
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
                                        Textarea::make('notes')
                                            ->rows(4)
                                            ->nullable()
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->persistTabInQueryString(),
            ]);
    }
}
