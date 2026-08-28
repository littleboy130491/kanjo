<?php

namespace App\Filament\Admin\Resources\Spks\Schemas;

use App\Enums\DocumentStatus;
use App\Enums\UserRole;
use App\Filament\Admin\Resources\Clients\Schemas\ClientForm;
use App\Filament\Support\RichEditorHtml;
use App\Models\Client;
use App\Models\Company;
use App\Models\Proposal;
use App\Models\Spk;
use App\Services\DocumentNumberGenerator;
use App\Services\SpkTemplateRenderer;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
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

class SpkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('SPK')
                    ->tabs([
                        Tab::make('Document Info')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Document Numbering')
                                    ->schema([
                                        TextInput::make('document_number')
                                            ->label('SPK Number')
                                            ->helperText('Auto-generated unless edited manually.')
                                            ->maxLength(255)
                                            ->default(fn (Get $get): string => self::generateDocumentNumberPreview($get('spk_date')))
                                            ->placeholder('Auto-generated')
                                            ->required()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (?string $state, Set $set) => $set('document_number_override', filled($state))),
                                        Hidden::make('document_number_override')
                                            ->default(false)
                                            ->dehydrated(),
                                        TextInput::make('document_number_raw')
                                            ->label('Raw Number')
                                            ->numeric()
                                            ->minValue(1)
                                            ->default(fn (Get $get): int => self::generateNextDocumentRaw(
                                                filled($get('spk_date')) ? Carbon::parse($get('spk_date')) : now(),
                                            ))
                                            ->required(fn (): bool => self::canEditDocumentRawNumber())
                                            ->visible(fn (): bool => self::canEditDocumentRawNumber())
                                            ->dehydrated(fn (): bool => self::canEditDocumentRawNumber())
                                            ->rules(fn (Get $get, ?Spk $record): array => self::canEditDocumentRawNumber() ? [
                                                Rule::unique('spks', 'document_number_raw')
                                                    ->where(fn ($query) => $query
                                                        ->where('issue_month', self::resolveDocumentNumberDate($record, $get('spk_date'))->month)
                                                        ->where('issue_year', self::resolveDocumentNumberDate($record, $get('spk_date'))->year))
                                                    ->ignore($record?->getKey()),
                                            ] : []),
                                        TextInput::make('slug')
                                            ->label('Public Slug')
                                            ->placeholder('Auto-generated')
                                            ->helperText(fn (Get $get): string => 'Public URL: '.url('/spk/'.Str::slug((string) ($get('slug') ?: self::generateSlugPreview($get('spk_date'))))))
                                            ->default(fn (Get $get): string => self::generateSlugPreview($get('spk_date')))
                                            ->maxLength(255)
                                            ->unique(ignoreRecord: true)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (?string $state, Set $set) => $set('slug', filled($state) ? Str::slug($state) : null))
                                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Str::slug($state) : null),
                                    ])
                                    ->columns(3),

                                Section::make('Document Details')
                                    ->schema([
                                        DatePicker::make('spk_date')
                                            ->label('SPK Date')
                                            ->required()
                                            ->default(now())
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, Get $get, Set $set, ?Spk $record): void {
                                                if ($record || $get('document_number_override')) {
                                                    return;
                                                }

                                                $set('document_number', self::generateDocumentNumberFromRaw($get('document_number_raw'), $state));
                                            }),
                                        Select::make('status')
                                            ->options(DocumentStatus::class)
                                            ->enum(DocumentStatus::class)
                                            ->default(DocumentStatus::PUBLISHED)
                                            ->required(),
                                        Select::make('proposal_id')
                                            ->label('Proposal (Optional)')
                                            ->options(fn () => Proposal::query()
                                                ->orderByDesc('created_at')
                                                ->get()
                                                ->mapWithKeys(fn (Proposal $proposal) => [
                                                    $proposal->getKey() => (string) ($proposal->document_number ?: $proposal->slug ?: 'Proposal #'.$proposal->getKey()),
                                                ]))
                                            ->searchable()
                                            ->preload()
                                            ->placeholder('Not linked to a proposal')
                                            ->native(false),
                                    ])
                                    ->columns(3),
                            ]),

                        Tab::make('Parties')
                            ->icon('heroicon-o-user-group')
                            ->schema([
                                Section::make('Client Party')
                                    ->schema([
                                        Select::make('client_id')
                                            ->label('Load from Client Database')
                                            ->options(fn () => Client::query()
                                                ->orderBy('company')
                                                ->get()
                                                ->mapWithKeys(fn (Client $client) => [
                                                    $client->getKey() => (string) ($client->company ?: $client->name ?: 'Client #'.$client->getKey()),
                                                ]))
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->createOptionUsing(fn (array $data): int => Client::create($data)->getKey())
                                            ->createOptionForm(schema: [
                                                ClientForm::getClientInformationSection(),
                                                ClientForm::getNotesSection(),
                                            ])
                                            ->afterStateUpdated(function ($state, Set $set): void {
                                                $client = $state ? Client::find($state) : null;

                                                if (! $client) {
                                                    return;
                                                }

                                                $set('client_company', $client->company);
                                                $set('client_pic_name', $client->name);
                                                $set('client_address', $client->address);
                                            }),
                                        TextInput::make('client_company')
                                            ->label('Company Name')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('client_pic_name')
                                            ->label('PIC Name')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('client_pic_role')
                                            ->label('PIC Role')
                                            ->maxLength(255),
                                        Textarea::make('client_address')
                                            ->label('Company Address')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),

                                Section::make('Our Company Party')
                                    ->schema([
                                        Select::make('company_id')
                                            ->label('Load from Company Database')
                                            ->options(fn () => Company::query()
                                                ->orderBy('brand_name')
                                                ->get()
                                                ->mapWithKeys(fn (Company $company) => [
                                                    $company->getKey() => (string) ($company->brand_name ?: $company->company_name ?: 'Company #'.$company->getKey()),
                                                ]))
                                            ->default(fn () => Company::first()?->id)
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->afterStateUpdated(function ($state, Set $set): void {
                                                $company = $state ? Company::find($state) : null;

                                                if (! $company) {
                                                    return;
                                                }

                                                $set('company_name', $company->company_name);
                                                $set('company_address', $company->address);
                                            }),
                                        TextInput::make('company_name')
                                            ->label('Company Name')
                                            ->default(fn (): string => (string) (Company::first()?->company_name ?? ''))
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('company_pic_name')
                                            ->label('PIC Name')
                                            ->default(fn (): string => (string) data_get(Company::first()?->pic, '0.pic_name', ''))
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('company_pic_role')
                                            ->label('PIC Role')
                                            ->default(fn (): string => (string) data_get(Company::first()?->pic, '0.pic_role', ''))
                                            ->maxLength(255),
                                        Textarea::make('company_address')
                                            ->label('Company Address')
                                            ->default(fn (): string => (string) (Company::first()?->address ?? ''))
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),
                            ]),

                        Tab::make('Content')
                            ->icon('heroicon-o-pencil-square')
                            ->schema([
                                Section::make('Translation Settings')
                                    ->schema([
                                        Toggle::make('activate_translation')
                                            ->label('Activate Translation')
                                            ->helperText('Enable EN/ID document language switching for this SPK.')
                                            ->default(false),
                                    ]),
                                Section::make('Cover Title & Party Identification')
                                    ->description('Copied from SPK Content Defaults when this document is created. Edit them here for this SPK only.')
                                    ->schema([
                                        Translate::make()
                                            ->exclude(self::translatedFieldPaths(['title', 'party_identification', 'subject', 'content']))
                                            ->schema(fn (string $locale): array => [
                                                RichEditorHtml::configure(
                                                    RichEditor::make("title.{$locale}")
                                                        ->label('Title')
                                                        ->default(fn (): string => SpkTemplateRenderer::defaultForLocale('title', $locale)),
                                                )
                                                    ->enableToolbarButtons(['table'])
                                                    ->columnSpanFull(),
                                                RichEditorHtml::configure(
                                                    RichEditor::make("party_identification.{$locale}")
                                                        ->label('Party Identification')
                                                        ->default(fn (): string => SpkTemplateRenderer::toEditableTables(
                                                            SpkTemplateRenderer::defaultForLocale('party_identification', $locale),
                                                        )),
                                                )
                                                    ->enableToolbarButtons([
                                                        'table',
                                                        'tableAddColumnBefore',
                                                        'tableAddColumnAfter',
                                                        'tableDeleteColumn',
                                                        'tableAddRowBefore',
                                                        'tableAddRowAfter',
                                                        'tableDeleteRow',
                                                        'tableMergeCells',
                                                        'tableSplitCell',
                                                        'tableDelete',
                                                    ])
                                                    ->extraAttributes(['style' => 'min-height: 360px'])
                                                    ->columnSpanFull(),
                                                TextInput::make("subject.{$locale}")
                                                    ->label('Subject')
                                                    ->default(fn (): string => SpkTemplateRenderer::defaultForLocale('subject', $locale))
                                                    ->maxLength(255)
                                                    ->columnSpanFull(),
                                                RichEditorHtml::configure(
                                                    RichEditor::make("content.{$locale}")
                                                        ->label('Content')
                                                        ->helperText('Agreement articles and clauses. Party identification is a separate field above.')
                                                        ->default(fn (): string => SpkTemplateRenderer::defaultForLocale('content', $locale)),
                                                )
                                                    ->extraInputAttributes(['class' => 'spk-content-editor'], merge: true)
                                                    ->extraAttributes(['style' => 'min-height: 560px'])
                                                    ->columnSpanFull(),
                                            ])
                                            ->suffixLocaleLabel(),
                                    ]),
                            ]),

                        Tab::make('Access & Notes')
                            ->icon('heroicon-o-lock-closed')
                            ->schema([
                                Section::make('Client Access Override')
                                    ->schema([
                                        TextInput::make('access_username')
                                            ->label('Username')
                                            ->maxLength(255)
                                            ->helperText('Leave blank to use global document credentials.'),
                                        TextInput::make('access_password')
                                            ->label('Password')
                                            ->password()
                                            ->revealable()
                                            ->maxLength(255)
                                            ->helperText('Leave blank to use global document credentials.'),
                                    ])
                                    ->columns(2),
                                Section::make('Internal Notes')
                                    ->schema([
                                        Repeater::make('notes')
                                            ->schema([
                                                TextInput::make('note')
                                                    ->label('Note')
                                                    ->maxLength(500),
                                            ])
                                            ->columns(1)
                                            ->addActionLabel('Add Note'),
                                    ]),
                            ]),
                    ])
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }

    protected static function generateDocumentNumberPreview(mixed $spkDate): string
    {
        return DocumentNumberGenerator::generate('SPK', self::resolveSpkDate($spkDate))['document_number'];
    }

    protected static function generateDocumentNumberFromRaw(mixed $raw, mixed $spkDate): string
    {
        $date = self::resolveSpkDate($spkDate);
        $raw = filled($raw) ? (int) $raw : self::generateNextDocumentRaw($date);

        return DocumentNumberGenerator::regenerate('SPK', $raw, $date);
    }

    protected static function generateSlugPreview(mixed $spkDate): string
    {
        $date = self::resolveSpkDate($spkDate);
        $nextId = ((int) Spk::query()->max('id')) + 1;
        $raw = self::generateNextDocumentRaw($date);

        return sprintf('%d-%d%d%d', $nextId, $raw, $date->month, $date->year);
    }

    protected static function generateNextDocumentRaw(Carbon $date): int
    {
        $maxRaw = Spk::query()
            ->where('issue_month', $date->month)
            ->where('issue_year', $date->year)
            ->max('document_number_raw');

        return $maxRaw ? ((int) $maxRaw + 1) : 1;
    }

    protected static function resolveSpkDate(mixed $spkDate): Carbon
    {
        return filled($spkDate) ? Carbon::parse($spkDate) : now();
    }

    protected static function resolveDocumentNumberDate(?Spk $record, mixed $spkDate): Carbon
    {
        if ($record && filled($record->issue_month) && filled($record->issue_year)) {
            return Carbon::create((int) $record->issue_year, (int) $record->issue_month, 1);
        }

        return self::resolveSpkDate($spkDate);
    }

    protected static function canEditDocumentRawNumber(): bool
    {
        return auth()->user()?->hasRole(UserRole::SuperAdmin->value) ?? false;
    }

    /**
     * @param  array<int, string>  $fields
     * @return array<int, string>
     */
    protected static function translatedFieldPaths(array $fields): array
    {
        return collect($fields)
            ->flatMap(fn (string $field): array => collect(config('translatable.locales', ['en', 'id']))
                ->map(fn (string $locale): string => "{$field}.{$locale}")
                ->all())
            ->all();
    }
}
