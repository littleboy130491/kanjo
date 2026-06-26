<?php

namespace App\Filament\Admin\Resources\Invoices\Schemas;

use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Filament\Admin\Resources\Clients\Schemas\ClientForm;
use App\Filament\Admin\Support\TranslatableRepeaterSync;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Service;
use App\Services\DocumentNumberGenerator;
use Carbon\Carbon;
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
                                            ->helperText('Auto-generated unless edited manually.')
                                            ->maxLength(255)
                                            ->default(fn (Get $get): string => self::generateDocumentNumberPreview(
                                                'INV',
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
                                            ->rules(fn (Get $get, ?Invoice $record): array => self::canEditDocumentRawNumber() ? [
                                                Rule::unique('invoices', 'document_number_raw')
                                                    ->where(fn ($query) => $query
                                                        ->where('issue_month', self::resolveDocumentNumberDate($record, $get('issue_date'))->month)
                                                        ->where('issue_year', self::resolveDocumentNumberDate($record, $get('issue_date'))->year))
                                                    ->ignore($record?->getKey()),
                                            ] : []),
                                        TextInput::make('slug')
                                            ->label('Public Slug')
                                            ->placeholder('Auto-generated')
                                            ->helperText(fn (Get $get): string => 'Public URL: '.route('invoice.show', [
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

                                Section::make('Document Details')
                                    ->schema([
                                        Select::make('company_id')
                                            ->label('Company')
                                            ->options(fn () => Company::query()
                                                ->orderBy('brand_name')
                                                ->get()
                                                ->mapWithKeys(fn (Company $company) => [
                                                    $company->getKey() => (string) ($company->brand_name ?: $company->company_name ?: 'Untitled company'),
                                                ]))
                                            ->default(fn () => Company::first()?->id)
                                            ->required()
                                            ->searchable(),
                                        Select::make('proposal_id')
                                            ->label('Proposal (Optional)')
                                            ->options(fn () => \App\Models\Proposal::query()
                                                ->orderByDesc('created_at')
                                                ->get()
                                                ->mapWithKeys(fn (\App\Models\Proposal $proposal) => [
                                                    $proposal->getKey() => (string) ($proposal->document_number ?: $proposal->slug ?: 'Proposal #'.$proposal->getKey()),
                                                ]))
                                            ->searchable()
                                            ->preload()
                                            ->placeholder('Not linked to a proposal')
                                            ->native(false),
                                        Select::make('status')
                                            ->options(DocumentStatus::class)
                                            ->enum(DocumentStatus::class)
                                            ->default(DocumentStatus::PUBLISHED)
                                            ->required(),
                                        Select::make('payment_status')
                                            ->options(PaymentStatus::class)
                                            ->enum(PaymentStatus::class)
                                            ->default(PaymentStatus::UNPAID)
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function (PaymentStatus|string|null $state, Set $set) {
                                                $paymentStatus = $state instanceof PaymentStatus
                                                    ? $state
                                                    : (is_string($state) ? PaymentStatus::tryFrom($state) : null);

                                                if ($paymentStatus === PaymentStatus::PAID) {
                                                    $set('paid_at', now()->toDateString());
                                                } else {
                                                    $set('paid_at', null);
                                                }
                                            }),
                                        DatePicker::make('paid_at')
                                            ->label('Paid At')
                                            ->nullable()
                                            ->native(false)
                                            ->helperText('Date only. Auto-filled when payment status is set to Paid, and can be cleared.'),
                                        DatePicker::make('issue_date')
                                            ->required()
                                            ->default(now())
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, Get $get, Set $set, ?Invoice $record): void {
                                                if ($record || $get('document_number_override')) {
                                                    return;
                                                }

                                                $set('document_number', self::generateDocumentNumberFromRaw('INV', $get('document_number_raw'), $state));
                                            }),
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
                                            ->options(fn () => Client::query()
                                                ->orderBy('company')
                                                ->get()
                                                ->mapWithKeys(fn (Client $client) => [
                                                    $client->getKey() => (string) ($client->company ?: $client->name ?: 'Client #'.$client->getKey()),
                                                ]))
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

                                Section::make('Related Service (Optional)')
                                    ->schema([
                                        Select::make('service_id')
                                            ->label('Link to Service')
                                            ->options(fn () => Service::with('client')
                                                ->get()
                                                ->mapWithKeys(fn (Service $service) => [
                                                    $service->getKey() => (string) (($service->name ?: 'Service #'.$service->getKey()).' - '.($service->client?->company ?: $service->client?->name ?: 'No Client')),
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
                                Section::make('Translation Settings')
                                    ->schema([
                                        Toggle::make('activate_translation')
                                            ->label('Activate Translation')
                                            ->helperText('Enable EN/ID document language switching for this invoice.')
                                            ->default(false),
                                    ]),

                                Section::make('Invoice Items')
                                    ->schema([
                                        Translate::make()
                                            ->actions([
                                                TranslatableRepeaterSync::makeCopyToAllLocalesAction('items'),
                                            ])
                                            ->schema(fn (string $locale): array => [
                                                TranslatableRepeaterSync::configure(
                                                    Repeater::make('items'),
                                                    $locale,
                                                )
                                                    ->schema([
                                                        TextInput::make('title')
                                                            ->required()
                                                            ->maxLength(255)
                                                            ->columnSpan(1),
                                                        TextInput::make('price')
                                                            ->required()
                                                            ->numeric()
                                                            ->inputMode('decimal')
                                                            ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateTotals($get, $set))
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
                                                    ->live()
                                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateTotals($get, $set))
                                                    ->default([]),
                                            ])
                                            ->suffixLocaleLabel(),
                                    ]),

                                Section::make('Additional Info')
                                    ->schema([
                                        Translate::make()
                                            ->exclude(self::translatedFieldPaths('additional_info'))
                                            ->schema(fn (string $locale): array => [
                                                RichEditor::make("additional_info.{$locale}")
                                                    ->hiddenLabel()
                                                    ->columnSpanFull(),
                                            ]),
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
                                            ->suffix('%')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateTotals($get, $set)),
                                    ])
                                    ->columns(2),

                                Section::make('Totals')
                                    ->schema([
                                        TextInput::make('subtotal')
                                            ->readonly()
                                            ->default(0)
                                            ->prefix(fn (Get $get) => $get('currency'))
                                            ->placeholder('Auto-calculated'),
                                        TextInput::make('tax_amount')
                                            ->readonly()
                                            ->default(0)
                                            ->prefix(fn (Get $get) => $get('currency'))
                                            ->placeholder('Auto-calculated'),
                                        TextInput::make('total')
                                            ->readonly()
                                            ->default(0)
                                            ->prefix(fn (Get $get) => $get('currency'))
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

                                Section::make('Audit Information')
                                    ->schema([
                                        Placeholder::make('created_at_info')
                                            ->label('Created At')
                                            ->content(fn (?Invoice $record): string => $record?->created_at?->format('d M Y H:i:s') ?? '-'),
                                        Placeholder::make('created_by_info')
                                            ->label('Created By')
                                            ->content(fn (?Invoice $record): string => $record?->createdBy?->name ?? '-'),
                                        Placeholder::make('updated_at_info')
                                            ->label('Updated At')
                                            ->content(fn (?Invoice $record): string => $record?->updated_at?->format('d M Y H:i:s') ?? '-'),
                                        Placeholder::make('updated_by_info')
                                            ->label('Updated By')
                                            ->content(fn (?Invoice $record): string => $record?->updatedBy?->name ?? '-'),
                                    ])
                                    ->columns(2),
                            ]),
                    ])
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }

    protected static function generateDocumentNumberPreview(
        string $type,
        mixed $issueDate,
    ): string {
        $date = filled($issueDate) ? Carbon::parse($issueDate) : now();

        return DocumentNumberGenerator::generate($type, $date)['document_number'];
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
        $nextId = ((int) Invoice::query()->max('id')) + 1;
        $raw = self::generateNextDocumentRaw($date);

        return sprintf('%d-%d%d%d', $nextId, $raw, $date->month, $date->year);
    }

    protected static function generateNextDocumentRaw(Carbon $date): int
    {
        $maxRaw = Invoice::query()
            ->where('issue_month', $date->month)
            ->where('issue_year', $date->year)
            ->max('document_number_raw');

        return $maxRaw ? ((int) $maxRaw + 1) : 1;
    }

    protected static function resolveIssueDate(mixed $issueDate): Carbon
    {
        return filled($issueDate) ? Carbon::parse($issueDate) : now();
    }

    protected static function resolveDocumentNumberDate(?Invoice $record, mixed $issueDate): Carbon
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

    protected static function recalculateTotals(Get $get, Set $set): void
    {
        $items = self::extractItemsForTotal(
            $get('items')
            ?? $get('../items')
            ?? $get('../../items')
            ?? $get('../../../items')
            ?? $get('../../../../items')
            ?? $get('../../../../../items')
        );

        $subtotal = collect($items)
            ->sum(fn (mixed $item): float => (float) data_get($item, 'price', 0));

        $taxRate = (float) ($get('tax_rate') ?? 0);
        $taxAmount = $subtotal * ($taxRate / 100);
        $total = $subtotal + $taxAmount;

        $set('subtotal', round($subtotal, 2));
        $set('tax_amount', round($taxAmount, 2));
        $set('total', round($total, 2));
    }

    protected static function translatedFieldPaths(string $fieldKey): array
    {
        return collect(config('translatable.locales', ['en', 'id']))
            ->map(fn (string $locale): string => "{$fieldKey}.{$locale}")
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected static function extractItemsForTotal(mixed $items): array
    {
        return self::findItemRows($items) ?? [];
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    protected static function findItemRows(mixed $node): ?array
    {
        if (! is_array($node)) {
            return null;
        }

        if (self::isItemRowCollection($node)) {
            return array_values($node);
        }

        foreach ($node as $value) {
            $found = self::findItemRows($value);

            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    protected static function isItemRowCollection(array $rows): bool
    {
        if ($rows === []) {
            return true;
        }

        $values = array_values($rows);

        foreach ($values as $value) {
            if (! is_array($value) || ! self::isItemRow($value)) {
                return false;
            }
        }

        return true;
    }

    protected static function isItemRow(array $row): bool
    {
        return array_key_exists('price', $row)
            || array_key_exists('title', $row)
            || array_key_exists('description', $row);
    }
}
