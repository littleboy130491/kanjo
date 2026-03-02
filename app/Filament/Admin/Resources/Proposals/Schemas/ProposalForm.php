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
use Awcodes\Curator\Components\Forms\RichEditor\AttachCuratorMediaPlugin;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
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
                                            ->schema(fn(string $locale): array => [
                                                self::makeRichEditor('brief'),
                                            ])
                                            ->suffixLocaleLabel(),
                                    ]),

                                Section::make('Core Services')
                                    ->schema([
                                        Translate::make()
                                            ->schema(fn(string $locale): array => [
                                                self::makeRichEditor('core_services'),
                                            ])
                                            ->suffixLocaleLabel(),
                                    ]),

                                Section::make('Features')
                                    ->schema([
                                        Translate::make()
                                            ->schema(fn(string $locale): array => [
                                                self::makeRichEditor('features'),
                                            ])
                                            ->suffixLocaleLabel(),
                                    ]),

                                Section::make('Server')
                                    ->schema([
                                        Translate::make()
                                            ->schema(fn(string $locale): array => [
                                                self::makeRichEditor('server'),
                                            ])
                                            ->suffixLocaleLabel(),
                                    ]),

                                Section::make('Assets')
                                    ->schema([
                                        Translate::make()
                                            ->schema(fn(string $locale): array => [
                                                self::makeRichEditor('assets'),
                                            ])
                                            ->suffixLocaleLabel(),
                                    ]),

                                Section::make('Security')
                                    ->schema([
                                        Translate::make()
                                            ->schema(fn(string $locale): array => [
                                                self::makeRichEditor('security'),
                                            ])
                                            ->suffixLocaleLabel(),
                                    ]),

                                Section::make('Support')
                                    ->schema([
                                        Translate::make()
                                            ->schema(fn(string $locale): array => [
                                                self::makeRichEditor('support'),
                                            ])
                                            ->suffixLocaleLabel(),
                                    ]),

                                Section::make('Additional Benefits')
                                    ->schema([
                                        Translate::make()
                                            ->schema(fn(string $locale): array => [
                                                self::makeRichEditor('additional_benefit'),
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
                                                        TextInput::make('price')
                                                            ->label('Price')
                                                            ->numeric()
                                                            ->prefix(fn(Get $get) => $get('currency'))
                                                            ->required(),
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
                                            ->schema(fn(string $locale): array => [
                                                self::makeRichEditor('payment'),
                                            ])
                                            ->suffixLocaleLabel(),
                                    ]),

                                Section::make('Terms & Conditions')
                                    ->schema([
                                        Translate::make()
                                            ->schema(fn(string $locale): array => [
                                                self::makeRichEditor('terms_condition'),
                                            ])
                                            ->suffixLocaleLabel(),
                                    ]),

                                Section::make('Additional Info')
                                    ->schema([
                                        Translate::make()
                                            ->schema(fn(string $locale): array => [
                                                self::makeRichEditor('additional_info'),
                                            ])
                                            ->suffixLocaleLabel(),
                                    ]),

                                Section::make('Extra Content Brief')
                                    ->schema([
                                        Translate::make()
                                            ->schema(fn(string $locale): array => [
                                                self::makeRichEditor('extra_content_brief'),
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

    protected static function makeRichEditor(string $name): RichEditor
    {
        return RichEditor::make($name)
            ->enableToolbarButtons(['attachCuratorMedia'])
            ->plugins([AttachCuratorMediaPlugin::make()])
            ->extraAttributes(['style' => 'min-height: 200px'])
            ->columnSpanFull();
    }

    protected static function emptyTranslatedRepeaterRow(string $fieldKey): array
    {
        return match ($fieldKey) {
            'add_on' => [
                'name' => '',
                'description' => '',
                'price' => '',
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
        return [];
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
