<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use App\Services\DocumentNumberGenerator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Invoice extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    protected $appends = [
        'document_number_final',
    ];

    protected $fillable = [
        'document_number',
        'slug',
        'document_number_raw',
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
        'status',
        'payment_status',
        'paid_amount',
        'paid_at',
        'payment_method',
        'access_username',
        'access_password',
        'notes',
        'proposal_id',
        'user_id',
        'company_id',
        'client_id',
        'service_id',
    ];

    protected $translatable = [
        'items',
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
        'paid_amount' => 'decimal:2',
        'status' => DocumentStatus::class,
        'payment_status' => PaymentStatus::class,
        'items' => 'array',
        'notes' => 'array',
    ];

    protected function documentNumberFinal(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value, array $attributes): ?string => $attributes['document_number'] ?? null,
        );
    }

    protected static function booted()
    {
        static::saving(function ($invoice) {
            if (blank($invoice->slug)) {
                $invoice->slug = null;

                return;
            }

            $invoice->slug = Str::slug((string) $invoice->slug);
        });

        static::creating(function ($invoice) {
            // Set user_id from authenticated user if not provided
            if (empty($invoice->user_id) && auth()->check()) {
                $invoice->user_id = auth()->id();
            }

            // Always set issue_month and issue_year from issue_date
            if ($invoice->issue_date) {
                $date = Carbon::parse($invoice->issue_date);
                $invoice->issue_month = $date->month;
                $invoice->issue_year = $date->year;
            } else {
                $now = now();
                $invoice->issue_month = $now->month;
                $invoice->issue_year = $now->year;
            }

            // Always allocate the next raw number, even when display number is overridden
            $date = $invoice->issue_date ? Carbon::parse($invoice->issue_date) : now();
            $data = DocumentNumberGenerator::generate('INV', $date);
            $generatedDocumentNumber = $data['document_number'];

            $invoice->document_number_raw = $data['document_number_raw'];

            if (blank($invoice->document_number)) {
                $invoice->document_number = $generatedDocumentNumber;
                $invoice->document_number_override = false;

                return;
            }

            $invoice->document_number_override = $invoice->document_number !== $generatedDocumentNumber;
        });

        static::created(function ($invoice) {
            if (filled($invoice->slug)) {
                return;
            }

            $invoice->forceFill([
                'slug' => self::generateSlug(
                    (int) $invoice->id,
                    (int) $invoice->document_number_raw,
                    (int) $invoice->issue_month,
                    (int) $invoice->issue_year,
                ),
            ])->saveQuietly();
        });

        static::updating(function ($invoice) {
            // Update issue_month and issue_year if issue_date changes
            if ($invoice->isDirty('issue_date') && $invoice->issue_date) {
                $date = Carbon::parse($invoice->issue_date);
                $invoice->issue_month = $date->month;
                $invoice->issue_year = $date->year;
            }

            $date = $invoice->issue_date ? Carbon::parse($invoice->issue_date) : now();
            $generatedDocumentNumber = DocumentNumberGenerator::regenerate(
                'INV',
                (int) $invoice->document_number_raw,
                $date,
            );

            if ($invoice->isDirty('document_number')) {
                if (blank($invoice->document_number)) {
                    $invoice->document_number = $generatedDocumentNumber;
                    $invoice->document_number_override = false;

                    return;
                }

                $invoice->document_number_override = $invoice->document_number !== $generatedDocumentNumber;

                return;
            }

            if ($invoice->isDirty('issue_date') && ! $invoice->document_number_override) {
                $invoice->document_number = $generatedDocumentNumber;
            }
        });
    }

    private static function generateSlug(
        int $id,
        int $documentNumberRaw,
        int $issueMonth,
        int $issueYear,
    ): string {
        return sprintf('%d-%d%d%d', $id, $documentNumberRaw, $issueMonth, $issueYear);
    }

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }


    public function setAccessPasswordAttribute(?string $value): void
    {
        if (blank($value)) {
            $this->attributes['access_password'] = null;

            return;
        }

        $this->attributes['access_password'] = Hash::info($value)['algo'] !== null
            ? $value
            : Hash::make($value);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
