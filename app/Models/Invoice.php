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
        'document_number_suffix',
        'document_number_override',
        'document_number_manual',
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
            get: fn (): ?string => $this->document_number,
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

            // Always generate document number (even if overridden)
            $date = $invoice->issue_date ? Carbon::parse($invoice->issue_date) : now();
            $data = DocumentNumberGenerator::generate('INV', $date);

            $invoice->document_number_raw = $data['document_number_raw'];

            // If not overridden, use the generated suffix and full number
            if (!$invoice->document_number_override) {
                $invoice->document_number = $data['document_number'];
                $invoice->document_number_suffix = $data['document_number_suffix'];
            } else {
                if (filled($invoice->document_number_manual)) {
                    $invoice->document_number = $invoice->document_number_manual;

                    return;
                }

                // If overridden but no suffix provided, use default
                if (!$invoice->document_number_suffix) {
                    $invoice->document_number_suffix = $data['document_number_suffix'];
                }
                // Generate the full number with the custom suffix
                $invoice->document_number = DocumentNumberGenerator::regenerateWithSuffix(
                    'INV',
                    $data['document_number_raw'],
                    $date,
                    $invoice->document_number_suffix
                );
            }
        });

        static::created(function ($invoice) {
            if (filled($invoice->slug)) {
                return;
            }

            $invoice->forceFill([
                'slug' => self::generateSlug((int) $invoice->id, 'inv'),
            ])->saveQuietly();
        });

        static::updating(function ($invoice) {
            // Update issue_month and issue_year if issue_date changes
            if ($invoice->isDirty('issue_date') && $invoice->issue_date) {
                $date = Carbon::parse($invoice->issue_date);
                $invoice->issue_month = $date->month;
                $invoice->issue_year = $date->year;
            }

            // Regenerate document number with new suffix if overridden
            if ($invoice->document_number_override && $invoice->isDirty('document_number_manual') && filled($invoice->document_number_manual)) {
                $invoice->document_number = $invoice->document_number_manual;

                return;
            }

            if (
                $invoice->document_number_override
                && blank($invoice->document_number_manual)
                && $invoice->isDirty('document_number_suffix')
            ) {
                $date = $invoice->issue_date ? Carbon::parse($invoice->issue_date) : now();
                $invoice->document_number = DocumentNumberGenerator::regenerateWithSuffix(
                    'INV',
                    $invoice->document_number_raw,
                    $date,
                    $invoice->document_number_suffix
                );
            }
        });
    }

    private static function generateSlug(int $id, string $prefix): string
    {
        do {
            $slug = sprintf(
                '%s-%s-%s',
                $prefix,
                base_convert((string) $id, 10, 36),
                Str::lower(Str::random(8)),
            );
        } while (self::query()->where('slug', $slug)->exists());

        return $slug;
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
