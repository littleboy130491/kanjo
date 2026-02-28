<?php

namespace App\Filament\Admin\Resources\Proposals\Schemas;

use App\Enums\DocumentStatus;
use App\Filament\Admin\Resources\Clients\Schemas\ClientForm;
use App\Filament\Admin\Resources\Portfolios\Schemas\PortfolioForm;
use App\Models\Client;
use App\Models\Company;
use App\Models\Portfolio;
use App\Models\ProposalContentDefault;
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
use SolutionForest\FilamentTranslateField\Forms\Component\Translate;

class ProposalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Proposal Tabs')
                    ->tabs([
                        // Tab 1: Document Info
                        Tab::make('Document Info')
                            ->icon('heroicon-o-document')
                            ->schema([
                                Section::make('Document Numbering')
                                    ->schema([
                                        TextInput::make('document_number')
                                            ->label('Document Number')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->placeholder('Auto-generated'),
                                        TextInput::make('document_number_suffix')
                                            ->label('Suffix')
                                            ->default('NEW')
                                            ->maxLength(10)
                                            ->helperText('Default: NEW (e.g., REV for revision)'),
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

                                Section::make('Document Settings')
                                    ->schema([
                                        Select::make('company_id')
                                            ->label('Issuing Company')
                                            ->options(fn () => Company::pluck('brand_name', 'id'))
                                            ->default(fn () => Company::first()?->id)
                                            ->required()
                                            ->searchable(),
                                        Select::make('status')
                                            ->options(DocumentStatus::class)
                                            ->enum(DocumentStatus::class)
                                            ->default(DocumentStatus::DRAFT)
                                            ->required(),
                                        DatePicker::make('issue_date')
                                            ->label('Issue Date')
                                            ->default(now())
                                            ->required(),
                                        DatePicker::make('valid_until')
                                            ->label('Valid Until')
                                            ->helperText('Leave empty for infinite validity')
                                            ->default(now()->addDays(30))
                                            ->nullable(),
                                    ])
                                    ->columns(2),
                            ]),

                        // Tab 2: Client Info
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
                                            ->helperText('Select a client to auto-fill the fields below. The data will be saved to this proposal, not linked.')
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
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('client_phone')
                                            ->label('Phone')
                                            ->tel()
                                            ->maxLength(255),
                                    ])
                                    ->columns(2),
                            ]),

                        // Tab 3: Financials
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
                                            ->live()
                                            ->required(),
                                        TextInput::make('tax_rate')
                                            ->label('Tax Rate (%)')
                                            ->numeric()
                                            ->default(0)
                                            ->required()
                                            ->suffix('%'),
                                    ])
                                    ->columns(2),

                                Section::make('Offer 1 (Main Offer)')
                                    ->schema([
                                        TextInput::make('offer_name_1')
                                            ->label('Offer Name')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                        TextInput::make('offer_1_price')
                                            ->label('Price')
                                            ->numeric()
                                            ->prefix(fn (Get $get) => $get('currency'))
                                            ->required(),
                                        TextInput::make('offer_1_original_price')
                                            ->label('Original Price (if discounted)')
                                            ->numeric()
                                            ->prefix(fn (Get $get) => $get('currency'))
                                            ->nullable(),
                                        TextInput::make('offer_1_renewal_price')
                                            ->label('Renewal Price')
                                            ->numeric()
                                            ->prefix(fn (Get $get) => $get('currency'))
                                            ->helperText('Annual/periodic cost for renewals'),
                                        TextInput::make('offer_1_original_renewal_price')
                                            ->label('Original Renewal Price (if discounted)')
                                            ->numeric()
                                            ->prefix(fn (Get $get) => $get('currency'))
                                            ->nullable(),
                                    ])
                                    ->columns(2),

                                Section::make('Offer 2 (Alternative Offer)')
                                    ->schema([
                                        TextInput::make('offer_name_2')
                                            ->label('Offer Name')
                                            ->maxLength(255)
                                            ->nullable()
                                            ->columnSpanFull(),
                                        TextInput::make('offer_2_price')
                                            ->label('Price')
                                            ->numeric()
                                            ->prefix(fn (Get $get) => $get('currency'))
                                            ->nullable(),
                                        TextInput::make('offer_2_original_price')
                                            ->label('Original Price (if discounted)')
                                            ->numeric()
                                            ->prefix(fn (Get $get) => $get('currency'))
                                            ->nullable(),
                                        TextInput::make('offer_2_renewal_price')
                                            ->label('Renewal Price')
                                            ->numeric()
                                            ->prefix(fn (Get $get) => $get('currency'))
                                            ->nullable(),
                                        TextInput::make('offer_2_original_renewal_price')
                                            ->label('Original Renewal Price (if discounted)')
                                            ->numeric()
                                            ->prefix(fn (Get $get) => $get('currency'))
                                            ->nullable(),
                                    ])
                                    ->columns(2),
                            ]),

                        // Tab 4: Content EN/ID
                        Tab::make('Content (EN/ID)')
                            ->icon('heroicon-o-language')
                            ->schema([
                                Section::make('Brief')
                                    ->schema([
                                        Translate::make()
                                            ->schema(fn (string $locale): array => [
                                                Repeater::make('brief')
                                                    ->schema([
                                                        Textarea::make('content')
                                                            ->required()
                                                            ->rows(3)
                                                            ->columnSpanFull(),
                                                    ])
                                                    ->addable()
                                                    ->reorderable()
                                                    ->deletable()
                                                    ->default(self::defaultContentRows('brief', $locale))
                                                    ->columnSpanFull(),
                                            ])
                                            ->locales(['en', 'id'])
                                            ->suffixLocaleLabel(),
                                    ]),

                                Section::make('Core Services')
                                    ->schema([
                                        Translate::make()
                                            ->schema(fn (string $locale): array => [
                                                Repeater::make('core_services')
                                                    ->schema([
                                                        TextInput::make('service')
                                                            ->required()
                                                            ->maxLength(255),
                                                    ])
                                                    ->addable()
                                                    ->reorderable()
                                                    ->deletable()
                                                    ->default(self::defaultContentRows('core_services', $locale))
                                                    ->columnSpanFull(),
                                            ])
                                            ->locales(['en', 'id'])
                                            ->suffixLocaleLabel(),
                                    ]),

                                Section::make('Features')
                                    ->schema([
                                        Translate::make()
                                            ->schema(fn (string $locale): array => [
                                                Repeater::make('features')
                                                    ->schema([
                                                        TextInput::make('feature_name')
                                                            ->label('Feature Name')
                                                            ->required()
                                                            ->maxLength(255),
                                                        Textarea::make('feature_description')
                                                            ->label('Description')
                                                            ->rows(2)
                                                            ->columnSpanFull(),
                                                    ])
                                                    ->addable()
                                                    ->reorderable()
                                                    ->deletable()
                                                    ->default(self::defaultContentRows('features', $locale))
                                                    ->columns(1)
                                                    ->columnSpanFull(),
                                            ])
                                            ->locales(['en', 'id'])
                                            ->suffixLocaleLabel(),
                                    ]),

                                Section::make('Server')
                                    ->schema([
                                        Translate::make()
                                            ->schema(fn (string $locale): array => [
                                                Repeater::make('server')
                                                    ->schema([
                                                        TextInput::make('item')
                                                            ->required()
                                                            ->maxLength(255),
                                                    ])
                                                    ->addable()
                                                    ->reorderable()
                                                    ->deletable()
                                                    ->default(self::defaultContentRows('server', $locale))
                                                    ->columnSpanFull(),
                                            ])
                                            ->locales(['en', 'id'])
                                            ->suffixLocaleLabel(),
                                    ]),

                                Section::make('Assets')
                                    ->schema([
                                        Translate::make()
                                            ->schema(fn (string $locale): array => [
                                                Repeater::make('assets')
                                                    ->schema([
                                                        TextInput::make('item')
                                                            ->required()
                                                            ->maxLength(255),
                                                    ])
                                                    ->addable()
                                                    ->reorderable()
                                                    ->deletable()
                                                    ->default(self::defaultContentRows('assets', $locale))
                                                    ->columnSpanFull(),
                                            ])
                                            ->locales(['en', 'id'])
                                            ->suffixLocaleLabel(),
                                    ]),

                                Section::make('Security')
                                    ->schema([
                                        Translate::make()
                                            ->schema(fn (string $locale): array => [
                                                Repeater::make('security')
                                                    ->schema([
                                                        TextInput::make('item')
                                                            ->required()
                                                            ->maxLength(255),
                                                    ])
                                                    ->addable()
                                                    ->reorderable()
                                                    ->deletable()
                                                    ->default(self::defaultContentRows('security', $locale))
                                                    ->columnSpanFull(),
                                            ])
                                            ->locales(['en', 'id'])
                                            ->suffixLocaleLabel(),
                                    ]),

                                Section::make('Support')
                                    ->schema([
                                        Translate::make()
                                            ->schema(fn (string $locale): array => [
                                                Repeater::make('support')
                                                    ->schema([
                                                        TextInput::make('item')
                                                            ->required()
                                                            ->maxLength(255),
                                                    ])
                                                    ->addable()
                                                    ->reorderable()
                                                    ->deletable()
                                                    ->default(self::defaultContentRows('support', $locale))
                                                    ->columnSpanFull(),
                                            ])
                                            ->locales(['en', 'id'])
                                            ->suffixLocaleLabel(),
                                    ]),

                                Section::make('Additional Benefits')
                                    ->schema([
                                        Translate::make()
                                            ->schema(fn (string $locale): array => [
                                                Repeater::make('additional_benefit')
                                                    ->schema([
                                                        TextInput::make('benefit')
                                                            ->required()
                                                            ->maxLength(255),
                                                    ])
                                                    ->addable()
                                                    ->reorderable()
                                                    ->deletable()
                                                    ->default(self::defaultContentRows('additional_benefit', $locale))
                                                    ->columnSpanFull(),
                                            ])
                                            ->locales(['en', 'id'])
                                            ->suffixLocaleLabel(),
                                    ]),

                                Section::make('Add-ons')
                                    ->schema([
                                        Translate::make()
                                            ->schema(fn (string $locale): array => [
                                                Repeater::make('add_on')
                                                    ->schema([
                                                        TextInput::make('name')
                                                            ->label('Name')
                                                            ->required()
                                                            ->maxLength(255),
                                                        Textarea::make('description')
                                                            ->label('Description')
                                                            ->rows(2)
                                                            ->columnSpanFull(),
                                                        TextInput::make('price')
                                                            ->label('Price')
                                                            ->numeric()
                                                            ->prefix(fn (Get $get) => $get('currency'))
                                                            ->required(),
                                                    ])
                                                    ->addable()
                                                    ->reorderable()
                                                    ->deletable()
                                                    ->default(self::defaultContentRows('add_on', $locale))
                                                    ->columns(2)
                                                    ->columnSpanFull(),
                                            ])
                                            ->locales(['en', 'id'])
                                            ->suffixLocaleLabel(),
                                    ]),

                                Section::make('Payment Terms')
                                    ->schema([
                                        Translate::make()
                                            ->schema(fn (string $locale): array => [
                                                Repeater::make('payment')
                                                    ->schema([
                                                        Textarea::make('info')
                                                            ->label('Payment Info')
                                                            ->required()
                                                            ->rows(2)
                                                            ->columnSpanFull(),
                                                        TextInput::make('down_payment_amount')
                                                            ->label('Down Payment Amount')
                                                            ->numeric()
                                                            ->prefix(fn (Get $get) => $get('currency'))
                                                            ->nullable(),
                                                    ])
                                                    ->addable()
                                                    ->reorderable()
                                                    ->deletable()
                                                    ->default(self::defaultContentRows('payment', $locale))
                                                    ->columnSpanFull(),
                                            ])
                                            ->locales(['en', 'id'])
                                            ->suffixLocaleLabel(),
                                    ]),

                                Section::make('Terms & Conditions')
                                    ->schema([
                                        Translate::make()
                                            ->schema(fn (string $locale): array => [
                                                Repeater::make('terms_condition')
                                                    ->schema([
                                                        TextInput::make('title')
                                                            ->label('Title')
                                                            ->required()
                                                            ->maxLength(255),
                                                        Textarea::make('description')
                                                            ->label('Description')
                                                            ->required()
                                                            ->rows(3)
                                                            ->columnSpanFull(),
                                                    ])
                                                    ->addable()
                                                    ->reorderable()
                                                    ->deletable()
                                                    ->default(self::defaultContentRows('terms_condition', $locale))
                                                    ->columnSpanFull(),
                                            ])
                                            ->locales(['en', 'id'])
                                            ->suffixLocaleLabel(),
                                    ]),

                                Section::make('Offer 1 Project Timeline')
                                    ->schema([
                                        Translate::make()
                                            ->schema(fn (string $locale): array => [
                                                Repeater::make('offer_1_project_timeline')
                                                    ->schema([
                                                        TextInput::make('activity_name')
                                                            ->label('Activity')
                                                            ->required()
                                                            ->maxLength(255),
                                                        TextInput::make('activity_pic')
                                                            ->label('PIC')
                                                            ->required()
                                                            ->maxLength(255),
                                                        TextInput::make('activity_days')
                                                            ->label('Days')
                                                            ->numeric()
                                                            ->required(),
                                                    ])
                                                    ->addable()
                                                    ->reorderable()
                                                    ->deletable()
                                                    ->default(self::defaultContentRows('offer_1_project_timeline', $locale))
                                                    ->columns(3)
                                                    ->columnSpanFull(),
                                            ])
                                            ->locales(['en', 'id'])
                                            ->suffixLocaleLabel(),
                                    ]),

                                Section::make('Offer 2 Project Timeline')
                                    ->schema([
                                        Translate::make()
                                            ->schema(fn (string $locale): array => [
                                                Repeater::make('offer_2_project_timeline')
                                                    ->schema([
                                                        TextInput::make('activity_name')
                                                            ->label('Activity')
                                                            ->maxLength(255),
                                                        TextInput::make('activity_pic')
                                                            ->label('PIC')
                                                            ->maxLength(255),
                                                        TextInput::make('activity_days')
                                                            ->label('Days')
                                                            ->numeric(),
                                                    ])
                                                    ->addable()
                                                    ->reorderable()
                                                    ->deletable()
                                                    ->default(self::defaultContentRows('offer_2_project_timeline', $locale))
                                                    ->columns(3)
                                                    ->columnSpanFull(),
                                            ])
                                            ->locales(['en', 'id'])
                                            ->suffixLocaleLabel(),
                                    ]),
                            ]),

                        // Tab 5: Portfolios
                        Tab::make('Portfolios')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                Section::make('Linked Portfolios')
                                    ->schema([
                                        Select::make('portfolios')
                                            ->label('Select Portfolios')
                                            ->relationship('portfolios', 'name')
                                            ->multiple()
                                            ->preload()
                                            ->searchable()
                                            ->createOptionUsing(function (array $data): int {
                                                $portfolio = Portfolio::create($data);

                                                return $portfolio->getKey();
                                            })
                                            ->createOptionForm(schema: [
                                                PortfolioForm::getPortfolioInformationSection(),
                                            ])
                                            ->helperText('Link portfolios from your portfolio database to this proposal'),
                                    ]),
                            ]),

                        // Tab 6: Internal
                        Tab::make('Internal')
                            ->icon('heroicon-o-lock-closed')
                            ->schema([
                                Section::make('Access Credentials')
                                    ->schema([
                                        TextInput::make('access_username')
                                            ->label('Access Username')
                                            ->maxLength(255)
                                            ->nullable()
                                            ->helperText('Leave empty to use global credentials'),
                                        TextInput::make('access_password')
                                            ->label('Access Password')
                                            ->password()
                                            ->maxLength(255)
                                            ->nullable()
                                            ->helperText('Leave empty to use global credentials'),
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

    protected static function defaultContentRows(string $fieldKey, string $locale): array
    {
        $globalDefault = ProposalContentDefault::query()
            ->where('field_key', ProposalContentDefault::GLOBAL_FIELD_KEY)
            ->first();

        if ($globalDefault instanceof ProposalContentDefault) {
            $value = match ($locale) {
                'en' => data_get($globalDefault->value_en, $fieldKey, []),
                'id' => data_get($globalDefault->value_id, $fieldKey, []),
                default => [],
            };

            if (is_array($value)) {
                return $value;
            }
        }

        // Backward compatibility for earlier per-field records.
        $legacyDefault = ProposalContentDefault::query()
            ->where('field_key', $fieldKey)
            ->first();

        if ($legacyDefault instanceof ProposalContentDefault) {
            return match ($locale) {
                'en' => $legacyDefault->value_en ?? [],
                'id' => $legacyDefault->value_id ?? [],
                default => [],
            };
        }

        return self::fallbackContentRows($fieldKey, $locale);
    }

    protected static function fallbackContentRows(string $fieldKey, string $locale): array
    {
        $defaults = [
            'en' => [
                'features' => [
                    [
                        'feature_name' => 'Responsive Design',
                        'feature_description' => 'Optimized display and usability on mobile, tablet, and desktop devices.',
                    ],
                    [
                        'feature_name' => 'Admin Dashboard',
                        'feature_description' => 'Secure dashboard to manage content, users, and key business data.',
                    ],
                    [
                        'feature_name' => 'SEO Foundation',
                        'feature_description' => 'Basic technical SEO setup for indexing, metadata, and performance readiness.',
                    ],
                ],
            ],
            'id' => [
                'features' => [
                    [
                        'feature_name' => 'Desain Responsif',
                        'feature_description' => 'Tampilan dan pengalaman yang optimal di perangkat mobile, tablet, dan desktop.',
                    ],
                    [
                        'feature_name' => 'Dashboard Admin',
                        'feature_description' => 'Dashboard aman untuk mengelola konten, pengguna, dan data bisnis utama.',
                    ],
                    [
                        'feature_name' => 'Fondasi SEO',
                        'feature_description' => 'Pengaturan SEO teknis dasar untuk indexing, metadata, dan kesiapan performa.',
                    ],
                ],
            ],
        ][$locale] ?? [];

        return $defaults[$fieldKey] ?? [];
    }
}
