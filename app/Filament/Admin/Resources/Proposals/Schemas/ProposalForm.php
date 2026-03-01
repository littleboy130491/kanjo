<?php

namespace App\Filament\Admin\Resources\Proposals\Schemas;

use App\Enums\DocumentStatus;
use App\Filament\Admin\Resources\Clients\Schemas\ClientForm;
use App\Filament\Admin\Resources\Portfolios\Schemas\PortfolioForm;
use App\Models\Client;
use App\Models\Company;
use App\Models\Portfolio;
use App\Models\Proposal;
use App\Models\ProposalContentDefault;
use App\Filament\Admin\Support\TranslatableRepeaterSync;
use App\Services\DocumentNumberGenerator;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
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
                                            ->helperText('Defaults to the next auto-generated number. You can edit it directly to override.')
                                            ->maxLength(255)
                                            ->default(fn(Get $get): string => self::generateDocumentNumberPreview(
                                                'QUO',
                                                $get('issue_date'),
                                            ))
                                            ->placeholder('Auto-generated')
                                            ->live(onBlur: true),
                                        TextInput::make('slug')
                                            ->label('Public Slug')
                                            ->placeholder('Auto-generated')
                                            ->helperText(fn(Get $get): string => 'Public URL: ' . route('proposal.show', [
                                                'slug' => Str::slug((string) ($get('slug') ?: self::generateSlugPreview($get('issue_date')))),
                                            ]))
                                            ->default(fn(Get $get): string => self::generateSlugPreview($get('issue_date')))
                                            ->maxLength(255)
                                            ->unique(ignoreRecord: true)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn(?string $state, callable $set) => $set('slug', filled($state) ? Str::slug($state) : null))
                                            ->dehydrateStateUsing(fn(?string $state): ?string => filled($state) ? Str::slug($state) : null),
                                    ])
                                    ->columns(2),

                                Section::make('Document Settings')
                                    ->schema([
                                        Select::make('company_id')
                                            ->label('Issuing Company')
                                            ->options(fn() => Company::pluck('brand_name', 'id'))
                                            ->default(fn() => Company::first()?->id)
                                            ->required()
                                            ->searchable(),
                                        Select::make('status')
                                            ->options(DocumentStatus::class)
                                            ->enum(DocumentStatus::class)
                                            ->default(DocumentStatus::PUBLISHED)
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
                                            ->options(fn() => Client::orderBy('company')->pluck('company', 'id'))
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
                                            ->prefix(fn(Get $get) => $get('currency'))
                                            ->required(),
                                        TextInput::make('offer_1_original_price')
                                            ->label('Original Price (if discounted)')
                                            ->numeric()
                                            ->prefix(fn(Get $get) => $get('currency'))
                                            ->nullable(),
                                        TextInput::make('offer_1_renewal_price')
                                            ->label('Renewal Price')
                                            ->numeric()
                                            ->prefix(fn(Get $get) => $get('currency'))
                                            ->helperText('Annual/periodic cost for renewals'),
                                        TextInput::make('offer_1_original_renewal_price')
                                            ->label('Original Renewal Price (if discounted)')
                                            ->numeric()
                                            ->prefix(fn(Get $get) => $get('currency'))
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
                                            ->prefix(fn(Get $get) => $get('currency'))
                                            ->nullable(),
                                        TextInput::make('offer_2_original_price')
                                            ->label('Original Price (if discounted)')
                                            ->numeric()
                                            ->prefix(fn(Get $get) => $get('currency'))
                                            ->nullable(),
                                        TextInput::make('offer_2_renewal_price')
                                            ->label('Renewal Price')
                                            ->numeric()
                                            ->prefix(fn(Get $get) => $get('currency'))
                                            ->nullable(),
                                        TextInput::make('offer_2_original_renewal_price')
                                            ->label('Original Renewal Price (if discounted)')
                                            ->numeric()
                                            ->prefix(fn(Get $get) => $get('currency'))
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
                                            ->actions([
                                                TranslatableRepeaterSync::makeCopyToAllLocalesAction('brief'),
                                            ])
                                            ->schema(fn(string $locale): array => [
                                                TranslatableRepeaterSync::configure(
                                                    Repeater::make('brief'),
                                                    $locale,
                                                    emptyRowTemplate: ['content' => ''],
                                                )
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
                                            ->suffixLocaleLabel(),
                                    ]),

                                Section::make('Core Services')
                                    ->schema([
                                        Translate::make()
                                            ->actions([
                                                TranslatableRepeaterSync::makeCopyToAllLocalesAction('core_services'),
                                            ])
                                            ->schema(fn(string $locale): array => [
                                                TranslatableRepeaterSync::configure(
                                                    Repeater::make('core_services'),
                                                    $locale,
                                                    emptyRowTemplate: ['service' => ''],
                                                )
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
                                            ->suffixLocaleLabel(),
                                    ]),

                                Section::make('Features')
                                    ->schema([
                                        Translate::make()
                                            ->actions([
                                                TranslatableRepeaterSync::makeCopyToAllLocalesAction('features'),
                                            ])
                                            ->schema(fn(string $locale): array => [
                                                TranslatableRepeaterSync::configure(
                                                    Repeater::make('features'),
                                                    $locale,
                                                    emptyRowTemplate: ['feature_name' => '', 'feature_description' => ''],
                                                )
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
                                            ->suffixLocaleLabel(),
                                    ]),

                                Section::make('Server')
                                    ->schema([
                                        Translate::make()
                                            ->actions([
                                                TranslatableRepeaterSync::makeCopyToAllLocalesAction('server'),
                                            ])
                                            ->schema(fn(string $locale): array => [
                                                TranslatableRepeaterSync::configure(
                                                    Repeater::make('server'),
                                                    $locale,
                                                    emptyRowTemplate: ['item' => ''],
                                                )
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
                                            ->suffixLocaleLabel(),
                                    ]),

                                Section::make('Assets')
                                    ->schema([
                                        Translate::make()
                                            ->actions([
                                                TranslatableRepeaterSync::makeCopyToAllLocalesAction('assets'),
                                            ])
                                            ->schema(fn(string $locale): array => [
                                                TranslatableRepeaterSync::configure(
                                                    Repeater::make('assets'),
                                                    $locale,
                                                    emptyRowTemplate: ['item' => ''],
                                                )
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
                                            ->suffixLocaleLabel(),
                                    ]),

                                Section::make('Security')
                                    ->schema([
                                        Translate::make()
                                            ->actions([
                                                TranslatableRepeaterSync::makeCopyToAllLocalesAction('security'),
                                            ])
                                            ->schema(fn(string $locale): array => [
                                                TranslatableRepeaterSync::configure(
                                                    Repeater::make('security'),
                                                    $locale,
                                                    emptyRowTemplate: ['item' => ''],
                                                )
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
                                            ->suffixLocaleLabel(),
                                    ]),

                                Section::make('Support')
                                    ->schema([
                                        Translate::make()
                                            ->actions([
                                                TranslatableRepeaterSync::makeCopyToAllLocalesAction('support'),
                                            ])
                                            ->schema(fn(string $locale): array => [
                                                TranslatableRepeaterSync::configure(
                                                    Repeater::make('support'),
                                                    $locale,
                                                    emptyRowTemplate: ['item' => ''],
                                                )
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
                                            ->suffixLocaleLabel(),
                                    ]),

                                Section::make('Additional Benefits')
                                    ->schema([
                                        Translate::make()
                                            ->actions([
                                                TranslatableRepeaterSync::makeCopyToAllLocalesAction('additional_benefit'),
                                            ])
                                            ->schema(fn(string $locale): array => [
                                                TranslatableRepeaterSync::configure(
                                                    Repeater::make('additional_benefit'),
                                                    $locale,
                                                    emptyRowTemplate: ['benefit' => ''],
                                                )
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
                                            ->suffixLocaleLabel(),
                                    ]),

                                Section::make('Add-ons')
                                    ->schema([
                                        Translate::make()
                                            ->actions([
                                                TranslatableRepeaterSync::makeCopyToAllLocalesAction('add_on'),
                                            ])
                                            ->schema(fn(string $locale): array => [
                                                TranslatableRepeaterSync::configure(
                                                    Repeater::make('add_on'),
                                                    $locale,
                                                    emptyRowTemplate: ['name' => '', 'description' => '', 'price' => ''],
                                                )
                                                    ->schema([
                                                        TextInput::make('name')
                                                            ->label('Name')
                                                            ->required()
                                                            ->maxLength(255),
                                                        Textarea::make('description')
                                                            ->label('Description')
                                                            ->rows(2)
                                                            ->columnSpanFull(),
                                                        TranslatableRepeaterSync::permanentlySynced(
                                                            TextInput::make('price')
                                                                ->label('Price')
                                                                ->numeric()
                                                                ->prefix(fn(Get $get) => $get('currency'))
                                                                ->required()
                                                        ),
                                                    ])
                                                    ->addable()
                                                    ->reorderable()
                                                    ->deletable()
                                                    ->default(self::defaultContentRows('add_on', $locale))
                                                    ->columns(2)
                                                    ->columnSpanFull(),
                                            ])
                                            ->suffixLocaleLabel(),
                                    ]),

                                Section::make('Payment Terms')
                                    ->schema([
                                        Translate::make()
                                            ->actions([
                                                TranslatableRepeaterSync::makeCopyToAllLocalesAction('payment'),
                                            ])
                                            ->schema(fn(string $locale): array => [
                                                TranslatableRepeaterSync::configure(
                                                    Repeater::make('payment'),
                                                    $locale,
                                                    emptyRowTemplate: ['info' => '', 'down_payment_amount' => ''],
                                                )
                                                    ->schema([
                                                        Textarea::make('info')
                                                            ->label('Payment Info')
                                                            ->required()
                                                            ->rows(2)
                                                            ->columnSpanFull(),
                                                        TextInput::make('down_payment_amount')
                                                            ->label('Down Payment Amount')
                                                            ->numeric()
                                                            ->prefix(fn(Get $get) => $get('currency'))
                                                            ->nullable(),
                                                    ])
                                                    ->addable()
                                                    ->reorderable()
                                                    ->deletable()
                                                    ->default(self::defaultContentRows('payment', $locale))
                                                    ->columnSpanFull(),
                                            ])
                                            ->suffixLocaleLabel(),
                                    ]),

                                Section::make('Terms & Conditions')
                                    ->schema([
                                        Translate::make()
                                            ->actions([
                                                TranslatableRepeaterSync::makeCopyToAllLocalesAction('terms_condition'),
                                            ])
                                            ->schema(fn(string $locale): array => [
                                                TranslatableRepeaterSync::configure(
                                                    Repeater::make('terms_condition'),
                                                    $locale,
                                                    emptyRowTemplate: ['title' => '', 'description' => ''],
                                                )
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
                                            ->suffixLocaleLabel(),
                                    ]),

                                Section::make('Offer 1 Project Timeline')
                                    ->schema([
                                        Translate::make()
                                            ->actions([
                                                TranslatableRepeaterSync::makeCopyToAllLocalesAction('offer_1_project_timeline'),
                                            ])
                                            ->schema(fn(string $locale): array => [
                                                TranslatableRepeaterSync::configure(
                                                    Repeater::make('offer_1_project_timeline'),
                                                    $locale,
                                                    emptyRowTemplate: ['activity_name' => '', 'activity_pic' => '', 'activity_days' => ''],
                                                )
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
                                            ->suffixLocaleLabel(),
                                    ]),

                                Section::make('Offer 2 Project Timeline')
                                    ->schema([
                                        Translate::make()
                                            ->actions([
                                                TranslatableRepeaterSync::makeCopyToAllLocalesAction('offer_2_project_timeline'),
                                            ])
                                            ->schema(fn(string $locale): array => [
                                                TranslatableRepeaterSync::configure(
                                                    Repeater::make('offer_2_project_timeline'),
                                                    $locale,
                                                    emptyRowTemplate: ['activity_name' => '', 'activity_pic' => '', 'activity_days' => ''],
                                                )
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

    protected static function emptyTranslatedRepeaterRow(string $fieldKey): array
    {
        return match ($fieldKey) {
            'brief' => ['content' => ''],
            'core_services' => ['service' => ''],
            'features' => [
                'feature_name' => '',
                'feature_description' => '',
            ],
            'server', 'assets', 'security', 'support' => ['item' => ''],
            'additional_benefit' => ['benefit' => ''],
            'add_on' => [
                'name' => '',
                'description' => '',
                'price' => '',
            ],
            'payment' => [
                'info' => '',
                'down_payment_amount' => '',
            ],
            'terms_condition' => [
                'title' => '',
                'description' => '',
            ],
            'offer_1_project_timeline', 'offer_2_project_timeline' => [
                'activity_name' => '',
                'activity_pic' => '',
                'activity_days' => '',
            ],
            default => [],
        };
    }

    protected static function defaultContentRows(string $fieldKey, string $locale): array
    {
        $globalDefault = ProposalContentDefault::query()
            ->where('field_key', ProposalContentDefault::GLOBAL_FIELD_KEY)
            ->first();

        if ($globalDefault instanceof ProposalContentDefault) {
            $translations = $globalDefault->getTranslations('value');
            $value = data_get($translations, "{$locale}.{$fieldKey}", []);

            if (is_array($value)) {
                return $value;
            }

            $legacyValue = match ($locale) {
                'en' => data_get($globalDefault->getAttribute('value_en'), $fieldKey, []),
                'id' => data_get($globalDefault->getAttribute('value_id'), $fieldKey, []),
                default => [],
            };

            if (is_array($legacyValue)) {
                return $legacyValue;
            }
        }

        // Backward compatibility for earlier per-field records.
        $legacyDefault = ProposalContentDefault::query()
            ->where('field_key', $fieldKey)
            ->first();

        if ($legacyDefault instanceof ProposalContentDefault) {
            $translations = $legacyDefault->getTranslations('value');
            $value = data_get($translations, $locale, []);

            if (is_array($value)) {
                return $value;
            }

            return match ($locale) {
                'en' => $legacyDefault->getAttribute('value_en') ?? [],
                'id' => $legacyDefault->getAttribute('value_id') ?? [],
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

    protected static function generateDocumentNumberPreview(
        string $type,
        mixed $issueDate,
    ): string {
        $date = filled($issueDate) ? Carbon::parse($issueDate) : now();
        $raw = self::generateNextDocumentRaw($date);
        $romanMonth = DocumentNumberGenerator::toRoman($date->month);

        return sprintf('%s/%03d/%s/%s/NEW', $type, $raw, $romanMonth, $date->format('y'));
    }

    protected static function generateSlugPreview(mixed $issueDate): string
    {
        $date = filled($issueDate) ? Carbon::parse($issueDate) : now();
        $nextId = ((int) Proposal::query()->max('id')) + 1;
        $raw = self::generateNextDocumentRaw($date);

        return sprintf('%d-%d%d%d', $nextId, $raw, $date->month, $date->year);
    }

    protected static function generateNextDocumentRaw(Carbon $date): int
    {
        $maxRaw = Proposal::query()
            ->where('issue_month', $date->month)
            ->where('issue_year', $date->year)
            ->max('document_number_raw');

        return $maxRaw ? ((int) $maxRaw + 1) : 1;
    }
}
