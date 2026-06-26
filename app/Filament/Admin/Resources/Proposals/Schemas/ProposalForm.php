<?php

namespace App\Filament\Admin\Resources\Proposals\Schemas;

use App\Enums\DocumentStatus;
use App\Enums\UserRole;
use App\Filament\Admin\Resources\Clients\Schemas\ClientForm;
use App\Filament\Admin\Support\TranslatableRepeaterSync;
use App\Models\Client;
use App\Models\Company;
use App\Models\Proposal;
use App\Models\ProposalContentDefault;
use App\Services\DocumentNumberGenerator;
use Awcodes\Curator\Components\Forms\RichEditor\AttachCuratorMediaPlugin;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
                                            ->helperText('Auto-generated unless edited manually.')
                                            ->maxLength(255)
                                            ->default(fn (Get $get): string => self::generateDocumentNumberPreview(
                                                'QUO',
                                                $get('issue_date'),
                                            ))
                                            ->placeholder('Auto-generated')
                                            ->required()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (?string $state, Set $set) => $set('document_number_override', filled($state))),
                                        Hidden::make('document_number_override')
                                            ->default(false)
                                            ->dehydrated(),
                                        TextInput::make('document_number_raw')
                                            ->label('Raw Number')
                                            ->helperText('Editable sequence number for the selected issue month.')
                                            ->numeric()
                                            ->minValue(1)
                                            ->default(fn (Get $get): int => self::generateNextDocumentRaw(
                                                filled($get('issue_date')) ? Carbon::parse($get('issue_date')) : now(),
                                            ))
                                            ->required(fn (): bool => self::canEditDocumentRawNumber())
                                            ->visible(fn (): bool => self::canEditDocumentRawNumber())
                                            ->dehydrated(fn (): bool => self::canEditDocumentRawNumber())
                                            ->rules(fn (Get $get, ?Proposal $record): array => self::canEditDocumentRawNumber() ? [
                                                Rule::unique('proposals', 'document_number_raw')
                                                    ->where(fn ($query) => $query
                                                        ->where('issue_month', self::resolveDocumentNumberDate($record, $get('issue_date'))->month)
                                                        ->where('issue_year', self::resolveDocumentNumberDate($record, $get('issue_date'))->year))
                                                    ->ignore($record?->getKey()),
                                            ] : []),
                                        TextInput::make('slug')
                                            ->label('Public Slug')
                                            ->placeholder('Auto-generated')
                                            ->helperText(fn (Get $get): string => 'Public URL: '.route('proposal.show', [
                                                'slug' => Str::slug((string) ($get('slug') ?: self::generateSlugPreview($get('issue_date')))),
                                            ]))
                                            ->default(fn (Get $get): string => self::generateSlugPreview($get('issue_date')))
                                            ->maxLength(255)
                                            ->unique(ignoreRecord: true)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (?string $state, callable $set) => $set('slug', filled($state) ? Str::slug($state) : null))
                                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Str::slug($state) : null),
                                    ])
                                    ->columns(3),

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
                                            ->default(DocumentStatus::PUBLISHED)
                                            ->required(),
                                        DatePicker::make('issue_date')
                                            ->label('Issue Date')
                                            ->default(now())
                                            ->required()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, Get $get, Set $set, ?Proposal $record): void {
                                                if ($record || $get('document_number_override')) {
                                                    return;
                                                }

                                                $set('document_number', self::generateDocumentNumberFromRaw('QUO', $get('document_number_raw'), $state));
                                            }),
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
                                                        $set('client_address', $client->address);
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
                                        Textarea::make('client_address')
                                            ->label('Address')
                                            ->helperText('Optional. Use line breaks or <br> for multiple lines.')
                                            ->rows(3)
                                            ->columnSpanFull(),
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
                                Section::make('Translation Settings')
                                    ->schema([
                                        Toggle::make('activate_translation')
                                            ->label('Activate Translation')
                                            ->helperText('Enable EN/ID document language switching for this proposal.')
                                            ->default(false),
                                    ]),

                                Section::make('Brief')
                                    ->schema([
                                        self::makeTranslatedRichEditor('brief'),
                                    ]),

                                Section::make('Extra Content Brief')
                                    ->schema([
                                        self::makeTranslatedRichEditor('extra_content_brief', 'ex: Figma link or barcode'),
                                    ]),

                                Section::make('Core Services')
                                    ->schema([
                                        self::makeTranslatedRichEditor('core_services'),
                                    ]),

                                Section::make('Features')
                                    ->headerActions([
                                        self::makeLoadRichTextTemplateAction('features', sourceOptions: [
                                            'features' => 'Default Features',
                                            'ecommerce_features' => 'E-commerce Features',
                                        ]),
                                    ])
                                    ->schema([
                                        self::makeTranslatedRichEditor('features'),
                                    ]),

                                Section::make('Server')
                                    ->schema([
                                        self::makeTranslatedRichEditor('server'),
                                    ]),

                                Section::make('Assets')
                                    ->schema([
                                        self::makeTranslatedRichEditor('assets'),
                                    ]),

                                Section::make('Security')
                                    ->schema([
                                        self::makeTranslatedRichEditor('security'),
                                    ]),

                                Section::make('Support')
                                    ->schema([
                                        self::makeTranslatedRichEditor('support'),
                                    ]),

                                Section::make('Additional Benefits')
                                    ->headerActions([
                                        self::makeLoadRichTextTemplateAction('additional_benefit'),
                                    ])
                                    ->schema([
                                        self::makeTranslatedRichEditor('additional_benefit', 'Additional Benefits (For opsi 2)', false),
                                    ]),

                                Section::make('Add-ons')
                                    ->schema([
                                        Translate::make()
                                            ->actions([
                                                TranslatableRepeaterSync::makeCopyToAllLocalesAction('add_on'),
                                            ])
                                            ->schema(fn (string $locale): array => [
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

                                                            ->required(),
                                                    ])
                                                    ->addable()
                                                    ->reorderable()
                                                    ->deletable()
                                                    ->default(self::defaultContentRows('add_on', $locale))
                                                    ->columns(2)
                                                    ->columnSpanFull(),
                                            ]),
                                    ]),

                                Section::make('Payment Terms')
                                    ->schema([
                                        self::makeTranslatedRichEditor('payment'),
                                    ]),

                                Section::make('Additional Info')
                                    ->headerActions([
                                        self::makeLoadRichTextTemplateAction('additional_info', 'marketing_program'),
                                    ])
                                    ->schema([
                                        self::makeTranslatedRichEditor('additional_info', 'ex: for marketing program'),
                                    ]),

                                Section::make('FAQ')
                                    ->headerActions([
                                        self::makeLoadRichTextTemplateAction('faq'),
                                    ])
                                    ->schema([
                                        self::makeTranslatedRichEditor('faq'),
                                    ]),

                                Section::make('Our Process')
                                    ->headerActions([
                                        self::makeLoadRichTextTemplateAction('our_process'),
                                    ])
                                    ->schema([
                                        self::makeTranslatedRichEditor('our_process'),
                                    ]),

                                Section::make('About Us')
                                    ->schema([
                                        Section::make('About Us Content')
                                            ->headerActions([
                                                self::makeLoadRichTextTemplateAction('about_us'),
                                            ])
                                            ->schema([
                                                self::makeTranslatedRichEditor('about_us'),
                                            ])
                                            ->compact(),

                                        Placeholder::make('about_us_map_note')
                                            ->label('Google Map')
                                            ->content('The map embed is configured on the issuing company record.')
                                            ->columnSpanFull(),

                                        Section::make('Client Logos')
                                            ->headerActions([
                                                self::makeLoadRepeaterTemplateAction('client_logos'),
                                            ])
                                            ->schema([
                                                Repeater::make('client_logos')
                                                    ->schema([
                                                        TextInput::make('url')
                                                            ->label('Logo Image URL')
                                                            ->url()
                                                            ->required()
                                                            ->maxLength(2048)
                                                            ->columnSpanFull(),
                                                    ])
                                                    ->addable()
                                                    ->reorderable()
                                                    ->deletable()
                                                    ->default(self::defaultNonTranslatableRepeaterRows('client_logos'))
                                                    ->columnSpanFull(),
                                            ])
                                            ->compact(),

                                        Section::make('Video Testimonials')
                                            ->headerActions([
                                                self::makeLoadRepeaterTemplateAction('video_testimonials'),
                                            ])
                                            ->schema([
                                                Repeater::make('video_testimonials')
                                                    ->schema([
                                                        TextInput::make('url')
                                                            ->label('Video URL')
                                                            ->url()
                                                            ->required()
                                                            ->maxLength(2048)
                                                            ->columnSpanFull(),
                                                    ])
                                                    ->addable()
                                                    ->reorderable()
                                                    ->deletable()
                                                    ->default(self::defaultNonTranslatableRepeaterRows('video_testimonials'))
                                                    ->columnSpanFull(),
                                            ])
                                            ->compact(),
                                    ])
                                    ->columnSpanFull(),

                                Section::make('Offer 1 Project Timeline')
                                    ->headerActions([
                                        self::makeLoadTimelineTemplateAction('offer_1_project_timeline'),
                                    ])
                                    ->schema([
                                        Translate::make()
                                            ->actions([
                                                TranslatableRepeaterSync::makeCopyToAllLocalesAction('offer_1_project_timeline'),
                                            ])
                                            ->schema(fn (string $locale): array => [
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
                                            ]),
                                    ]),

                                Section::make('Offer 2 Project Timeline')
                                    ->headerActions([
                                        self::makeLoadTimelineTemplateAction('offer_2_project_timeline'),
                                    ])
                                    ->schema([
                                        Translate::make()
                                            ->actions([
                                                TranslatableRepeaterSync::makeCopyToAllLocalesAction('offer_2_project_timeline'),
                                            ])
                                            ->schema(fn (string $locale): array => [
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
                                            ]),
                                    ]),

                                Section::make('Terms & Conditions')
                                    ->schema([
                                        self::makeTranslatedRichEditor('terms_condition'),
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
                                            ->helperText('Link portfolios from your portfolio database to this proposal. Create new portfolios from the Portfolios resource.'),
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

                                Section::make('Audit Information')
                                    ->schema([
                                        Placeholder::make('created_at_info')
                                            ->label('Created At')
                                            ->content(fn (?Proposal $record): string => $record?->created_at?->format('d M Y H:i:s') ?? '-'),
                                        Placeholder::make('created_by_info')
                                            ->label('Created By')
                                            ->content(fn (?Proposal $record): string => $record?->createdBy?->name ?? '-'),
                                        Placeholder::make('updated_at_info')
                                            ->label('Updated At')
                                            ->content(fn (?Proposal $record): string => $record?->updated_at?->format('d M Y H:i:s') ?? '-'),
                                        Placeholder::make('updated_by_info')
                                            ->label('Updated By')
                                            ->content(fn (?Proposal $record): string => $record?->updatedBy?->name ?? '-'),
                                    ])
                                    ->columns(2),
                            ]),
                    ])
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }

    protected static function makeTranslatedRichEditor(string $fieldKey, ?string $label = null, bool $useDefault = true): Translate
    {
        return Translate::make()
            ->exclude(self::translatedFieldPaths($fieldKey))
            ->schema(fn (string $locale): array => [
                self::makeRichEditor("{$fieldKey}.{$locale}", $fieldKey, $locale, $label, $useDefault),
            ]);
    }

    protected static function translatedFieldPaths(string $fieldKey): array
    {
        return collect(config('translatable.locales', ['en', 'id']))
            ->map(fn (string $locale): string => "{$fieldKey}.{$locale}")
            ->all();
    }

    protected static function makeRichEditor(
        string $statePath,
        string $fieldKey,
        string $locale,
        ?string $label = null,
        bool $useDefault = true,
    ): RichEditor {
        $editor = RichEditor::make($statePath)
            ->enableToolbarButtons(['attachCuratorMedia'])
            ->plugins([AttachCuratorMediaPlugin::make()])
            ->afterStateHydrated(function (RichEditor $component): void {
                self::normalizeRichEditorRawState($component);
            })
            ->extraAttributes(['style' => 'min-height: 200px'])
            ->columnSpanFull();

        if ($useDefault) {
            $editor->default(self::defaultRichTextContent($fieldKey, $locale));
        }

        return filled($label)
            ? $editor->label($label)
            : $editor->hiddenLabel();
    }

    protected static function defaultRichTextContent(string $fieldKey, string $locale): ?string
    {
        $globalDefault = ProposalContentDefault::query()
            ->where('field_key', ProposalContentDefault::GLOBAL_FIELD_KEY)
            ->first();

        if ($globalDefault instanceof ProposalContentDefault) {
            $translations = $globalDefault->getTranslations('value');
            $value = data_get($translations, "{$locale}.{$fieldKey}");

            if (is_string($value)) {
                return $value;
            }

            $legacyValue = data_get($globalDefault->getAttribute("value_{$locale}"), $fieldKey);

            if (is_string($legacyValue)) {
                return $legacyValue;
            }
        }

        return null;
    }

    protected static function normalizeRichEditorRawState(RichEditor $component): void
    {
        $rawState = $component->getRawState();

        if (is_array($rawState)) {
            return;
        }

        foreach ($component->getStateCasts() as $stateCast) {
            $rawState = $stateCast->set($rawState);
        }

        $component->rawState($rawState);
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
            'video_testimonials' => [
                'url' => '',
            ],
            'client_logos' => [
                'url' => '',
            ],
            default => [],
        };
    }

    protected static function defaultNonTranslatableRepeaterRows(string $fieldKey): array
    {
        $globalDefault = ProposalContentDefault::query()
            ->where('field_key', ProposalContentDefault::GLOBAL_FIELD_KEY)
            ->first();

        if ($globalDefault instanceof ProposalContentDefault) {
            $sharedValue = ProposalContentDefault::resolveSharedJsonRepeaterValue(
                $globalDefault->getTranslations('value'),
                $fieldKey,
            );

            if ($sharedValue !== []) {
                return $sharedValue;
            }
        }

        return self::fallbackContentRows($fieldKey, config('app.locale', 'en'));
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

            $legacyValue = data_get($globalDefault->getAttribute("value_{$locale}"), $fieldKey, []);

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

            return $legacyDefault->getAttribute("value_{$locale}") ?? [];
        }

        return self::fallbackContentRows($fieldKey, $locale);
    }

    protected static function makeLoadTimelineTemplateAction(string $targetField): Action
    {
        $timelineOptions = array_filter(
            ProposalContentDefault::FIELD_OPTIONS,
            fn (string $key): bool => str_ends_with($key, '_project_timeline'),
            ARRAY_FILTER_USE_KEY,
        );

        return Action::make('load_timeline_template_'.$targetField)
            ->label('Load Template')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->form([
                Select::make('template_key')
                    ->label('Timeline Template')
                    ->options($timelineOptions)
                    ->required(),
            ])
            ->action(function (array $data, Set $set) use ($targetField): void {
                $templateKey = $data['template_key'];
                $allLocales = config('translatable.locales', ['en', 'id']);

                foreach ($allLocales as $locale) {
                    $rows = static::defaultContentRows($templateKey, $locale);
                    $set("{$targetField}.{$locale}", $rows);
                }
            });
    }

    protected static function makeLoadRepeaterTemplateAction(string $targetField): Action
    {
        return Action::make('load_repeater_template_'.$targetField)
            ->label('Load Template')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->action(function (Set $set) use ($targetField): void {
                $rows = static::defaultNonTranslatableRepeaterRows($targetField);
                $set($targetField, $rows);
            });
    }

    /**
     * @param  array<string, string>|null  $sourceOptions
     */
    protected static function makeLoadRichTextTemplateAction(
        string $targetField,
        ?string $sourceField = null,
        ?array $sourceOptions = null,
    ): Action {
        $lookupField = $sourceField ?? $targetField;
        $action = Action::make('load_rich_text_template_'.$targetField)
            ->label('Load Template')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray');

        if ($sourceOptions !== null) {
            $action->form([
                Select::make('template_key')
                    ->label('Template')
                    ->options($sourceOptions)
                    ->default($lookupField)
                    ->required(),
            ]);
        }

        return $action
            ->action(function (array $data, Set $set) use ($targetField, $lookupField, $sourceOptions): void {
                $selectedLookupField = $sourceOptions !== null
                    ? $data['template_key']
                    : $lookupField;
                $allLocales = config('translatable.locales', ['en', 'id']);

                foreach ($allLocales as $locale) {
                    $content = static::defaultRichTextContent($selectedLookupField, $locale) ?? '';
                    $set("{$targetField}.{$locale}", $content);
                }
            });
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

    protected static function generateDocumentNumberFromRaw(
        string $type,
        mixed $raw,
        mixed $issueDate,
    ): string {
        $date = self::resolveIssueDate($issueDate);
        $raw = filled($raw) ? (int) $raw : self::generateNextDocumentRaw($date);

        return DocumentNumberGenerator::regenerate($type, $raw, $date);
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

    protected static function resolveIssueDate(mixed $issueDate): Carbon
    {
        return filled($issueDate) ? Carbon::parse($issueDate) : now();
    }

    protected static function resolveDocumentNumberDate(?Proposal $record, mixed $issueDate): Carbon
    {
        if ($record && filled($record->issue_month) && filled($record->issue_year)) {
            return Carbon::create((int) $record->issue_year, (int) $record->issue_month, 1);
        }

        return self::resolveIssueDate($issueDate);
    }

    protected static function canEditDocumentRawNumber(): bool
    {
        return auth()->user()?->hasRole(UserRole::SuperAdmin->value) ?? false;
    }
}
