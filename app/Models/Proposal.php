<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Services\DocumentNumberGenerator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Proposal extends Model
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
        'valid_until',
        'currency',
        'tax_rate',
        'tax_amount',
        'total_amount',
        'brief',
        'core_services',
        'features',
        'server',
        'assets',
        'security',
        'support',
        'additional_benefit',
        'add_on',
        'payment',
        'terms_condition',
        'offer_name_1',
        'offer_1_price',
        'offer_1_original_price',
        'offer_1_renewal_price',
        'offer_1_original_renewal_price',
        'offer_1_project_timeline',
        'offer_name_2',
        'offer_2_price',
        'offer_2_original_price',
        'offer_2_renewal_price',
        'offer_2_original_renewal_price',
        'offer_2_project_timeline',
        'status',
        'access_username',
        'access_password',
        'notes',
        'user_id',
        'company_id',
        'client_id',
        'service_id',
    ];

    protected $translatable = [
        'brief',
        'core_services',
        'features',
        'server',
        'assets',
        'security',
        'support',
        'additional_benefit',
        'add_on',
        'payment',
        'terms_condition',
        'offer_1_project_timeline',
        'offer_2_project_timeline',
    ];

    protected $casts = [
        'document_number_override' => 'boolean',
        'issue_date' => 'date',
        'valid_until' => 'date',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'offer_1_price' => 'decimal:2',
        'offer_1_original_price' => 'decimal:2',
        'offer_1_renewal_price' => 'decimal:2',
        'offer_1_original_renewal_price' => 'decimal:2',
        'offer_2_price' => 'decimal:2',
        'offer_2_original_price' => 'decimal:2',
        'offer_2_renewal_price' => 'decimal:2',
        'offer_2_original_renewal_price' => 'decimal:2',
        'status' => DocumentStatus::class,
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
        static::saving(function ($proposal) {
            if (blank($proposal->slug)) {
                $proposal->slug = null;

                return;
            }

            $proposal->slug = Str::slug((string) $proposal->slug);
        });

        static::creating(function ($proposal) {
            // Set user_id from authenticated user if not provided
            if (empty($proposal->user_id) && auth()->check()) {
                $proposal->user_id = auth()->id();
            }

            // Always set issue_month and issue_year from issue_date
            if ($proposal->issue_date) {
                $date = Carbon::parse($proposal->issue_date);
                $proposal->issue_month = $date->month;
                $proposal->issue_year = $date->year;
            } else {
                $now = now();
                $proposal->issue_month = $now->month;
                $proposal->issue_year = $now->year;
            }

            // Always allocate the next raw number, even when display number is overridden
            $date = $proposal->issue_date ? Carbon::parse($proposal->issue_date) : now();
            $data = DocumentNumberGenerator::generate('QUO', $date);
            $generatedDocumentNumber = $data['document_number'];

            $proposal->document_number_raw = $data['document_number_raw'];

            if (blank($proposal->document_number)) {
                $proposal->document_number = $generatedDocumentNumber;
                $proposal->document_number_override = false;

                return;
            }

            $proposal->document_number_override = $proposal->document_number !== $generatedDocumentNumber;
        });

        static::created(function ($proposal) {
            if (filled($proposal->slug)) {
                return;
            }

            $proposal->forceFill([
                'slug' => self::generateSlug(
                    (int) $proposal->id,
                    (int) $proposal->document_number_raw,
                    (int) $proposal->issue_month,
                    (int) $proposal->issue_year,
                ),
            ])->saveQuietly();
        });

        static::updating(function ($proposal) {
            // Update issue_month and issue_year if issue_date changes
            if ($proposal->isDirty('issue_date') && $proposal->issue_date) {
                $date = Carbon::parse($proposal->issue_date);
                $proposal->issue_month = $date->month;
                $proposal->issue_year = $date->year;
            }

            $date = $proposal->issue_date ? Carbon::parse($proposal->issue_date) : now();
            $generatedDocumentNumber = DocumentNumberGenerator::regenerate(
                'QUO',
                (int) $proposal->document_number_raw,
                $date,
            );

            if ($proposal->isDirty('document_number')) {
                if (blank($proposal->document_number)) {
                    $proposal->document_number = $generatedDocumentNumber;
                    $proposal->document_number_override = false;

                    return;
                }

                $proposal->document_number_override = $proposal->document_number !== $generatedDocumentNumber;

                return;
            }

            if ($proposal->isDirty('issue_date') && ! $proposal->document_number_override) {
                $proposal->document_number = $generatedDocumentNumber;
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

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function portfolios()
    {
        return $this->belongsToMany(Portfolio::class);
    }
}
