<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use App\Models\Concerns\HasDocumentModelBehavior;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Invoice extends Model
{
    use HasDocumentModelBehavior;
    use HasFactory;
    use HasTranslations;
    use SoftDeletes;

    public string $documentNumberSuffix = 'NEW';

    protected $fillable = [
        'document_number',
        'slug',
        'document_number_raw',
        'document_number_override',
        'issue_month',
        'issue_year',
        'client_company',
        'client_name',
        'client_email',
        'client_phone',
        'issue_date',
        'due_date',
        'currency',
        'tax_rate',
        'tax_amount',
        'subtotal',
        'total',
        'items',
        'additional_info',
        'status',
        'payment_status',
        'paid_at',
        'access_username',
        'access_password',
        'notes',
        'proposal_id',
        'user_id',
        'updated_by',
        'company_id',
        'client_id',
        'service_id',
    ];

    protected $translatable = [
        'items',
        'additional_info',
    ];

    protected $casts = [
        'document_number_override' => 'boolean',
        'issue_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'status' => DocumentStatus::class,
        'payment_status' => PaymentStatus::class,
        'items' => 'array',
        'notes' => 'array',
    ];

    protected static function documentNumberType(): string
    {
        return 'INV';
    }

    protected static function beforeDocumentSaving(Model $model): void
    {
        self::recalculateTotals($model);
    }

    protected static function resolveDocumentSuffixForCreate(Model $model): ?string
    {
        return self::extractSuffixFromDocumentNumber($model->document_number)
            ?? $model->documentNumberSuffix
            ?? 'NEW';
    }

    protected static function resolveDocumentSuffixForUpdate(Model $model): ?string
    {
        return self::extractSuffixFromDocumentNumber($model->document_number) ?? 'NEW';
    }

    private static function recalculateTotals(Model $invoice): void
    {
        $invoice->items = self::normalizeTranslatedItemPrices($invoice->items);

        $items = self::extractItemsForTotal($invoice->items);
        $subtotal = collect($items)
            ->sum(fn (mixed $item): float => (float) data_get($item, 'price', 0));

        $taxRate = (float) ($invoice->tax_rate ?? 0);
        $taxAmount = $subtotal * ($taxRate / 100);

        $invoice->subtotal = round($subtotal, 2);
        $invoice->tax_amount = round($taxAmount, 2);
        $invoice->total = round($subtotal + $taxAmount, 2);
    }

    private static function normalizeTranslatedItemPrices(mixed $items): mixed
    {
        if (! is_array($items)) {
            return $items;
        }

        if (! isset($items['en'], $items['id']) || ! is_array($items['en']) || ! is_array($items['id'])) {
            return $items;
        }

        $enRows = $items['en'];
        $idRows = $items['id'];
        $enKeys = array_keys($enRows);
        $idKeys = array_keys($idRows);
        $maxRows = max(count($enKeys), count($idKeys));

        for ($index = 0; $index < $maxRows; $index++) {
            $enKey = $enKeys[$index] ?? null;
            $idKey = $idKeys[$index] ?? null;
            $enPrice = $enKey !== null ? data_get($enRows[$enKey], 'price') : null;
            $idPrice = $idKey !== null ? data_get($idRows[$idKey], 'price') : null;
            $resolvedPrice = filled($enPrice) ? $enPrice : $idPrice;

            if ($resolvedPrice === null) {
                continue;
            }

            if ($enKey !== null && is_array($enRows[$enKey])) {
                $enRows[$enKey]['price'] = $resolvedPrice;
            }

            if ($idKey !== null && is_array($idRows[$idKey])) {
                $idRows[$idKey]['price'] = $resolvedPrice;
            }
        }

        $items['en'] = $enRows;
        $items['id'] = $idRows;

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function extractItemsForTotal(mixed $items): array
    {
        return self::findItemRows($items) ?? [];
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    private static function findItemRows(mixed $node): ?array
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

    private static function isItemRowCollection(array $rows): bool
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

    private static function isItemRow(array $row): bool
    {
        return array_key_exists('price', $row)
            || array_key_exists('title', $row)
            || array_key_exists('description', $row);
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
