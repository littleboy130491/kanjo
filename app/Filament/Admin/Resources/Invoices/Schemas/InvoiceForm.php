<?php

namespace App\Filament\Admin\Resources\Invoices\Schemas;

use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\Clients\Schemas\ClientForm;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Service;
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
use Filament\Schemas\Components\Utilities\Set;
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
                                            ->helperText('Defaults to the next auto-generated number. You can edit it directly to override.')
                                            ->maxLength(255)
                                            ->default(fn(Get $get): string => self::generateDocumentNumberPreview(
                                                'INV',
                                                $get('issue_date'),
                                            ))
                                            ->placeholder('Auto-generated')
                                            ->live(onBlur: true),
                                        TextInput::make('slug')
                                            ->label('Public Slug')
                                            ->placeholder('Auto-generated')
                                            ->helperText(fn(Get $get): string => 'Public URL: ' . route('invoice.show', [
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

                                Section::make('Document Details')
                                    ->schema([
                                        Select::make('company_id')
                                            ->label('Company')
                                            ->options(fn() => Company::pluck('brand_name', 'id'))
                                            ->default(fn() => Company::first()?->id)
                                            ->required()
                                            ->searchable(),
                                        Select::make('proposal_id')
                                            ->label('Proposal (Optional)')
                                            ->options(fn() => \App\Models\Proposal::pluck('document_number', 'id'))
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
                                            ->options(fn() => Client::orderBy('company')->pluck('company', 'id'))
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
                                            ->options(fn() => Service::with('client')
                                                ->get()
                                                ->mapWithKeys(fn($service) => [
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
                                            ->actions([
                                                TranslatableRepeaterSync::makeCopyToAllLocalesAction('items'),
                                            ])
                                            ->schema(fn(string $locale): array => [
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
                                                            ->afterStateUpdated(fn(Get $get, Set $set) => self::recalculateTotals($get, $set))
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
                                                    ->afterStateUpdated(fn(Get $get, Set $set) => self::recalculateTotals($get, $set))
                                                    ->default([]),
                                            ])
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
                                            ->suffix('%')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn(Get $get, Set $set) => self::recalculateTotals($get, $set)),
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

    protected static function generateDocumentNumberPreview(
        string $type,
        mixed $issueDate,
    ): string {
        $date = filled($issueDate) ? Carbon::parse($issueDate) : now();

        return DocumentNumberGenerator::generate($type, $date)['document_number'];
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
            ->sum(fn(mixed $item): float => (float) data_get($item, 'price', 0));

        $taxRate = (float) ($get('tax_rate') ?? 0);
        $taxAmount = $subtotal * ($taxRate / 100);
        $total = $subtotal + $taxAmount;

        $set('subtotal', round($subtotal, 2));
        $set('tax_amount', round($taxAmount, 2));
        $set('total', round($total, 2));
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
        if (!is_array($node)) {
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
            if (!is_array($value) || !self::isItemRow($value)) {
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
