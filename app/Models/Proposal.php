<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Services\DocumentNumberGenerator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Proposal extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    protected $fillable = [
        'document_number',
        'document_number_raw',
        'document_number_suffix',
        'document_number_override',
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

    protected static function booted()
    {
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

            // Always generate document number (even if overridden)
            $date = $proposal->issue_date ? Carbon::parse($proposal->issue_date) : now();
            $data = DocumentNumberGenerator::generate('QUO', $date);

            $proposal->document_number_raw = $data['document_number_raw'];

            // If not overridden, use the generated suffix and full number
            if (! $proposal->document_number_override) {
                $proposal->document_number = $data['document_number'];
                $proposal->document_number_suffix = $data['document_number_suffix'];
            } else {
                // If overridden but no suffix provided, use default
                if (! $proposal->document_number_suffix) {
                    $proposal->document_number_suffix = $data['document_number_suffix'];
                }
                // Generate the full number with the custom suffix
                $proposal->document_number = DocumentNumberGenerator::regenerateWithSuffix(
                    'QUO',
                    $data['document_number_raw'],
                    $date,
                    $proposal->document_number_suffix
                );
            }
        });

        static::updating(function ($proposal) {
            // Update issue_month and issue_year if issue_date changes
            if ($proposal->isDirty('issue_date') && $proposal->issue_date) {
                $date = Carbon::parse($proposal->issue_date);
                $proposal->issue_month = $date->month;
                $proposal->issue_year = $date->year;
            }

            // Regenerate document number with new suffix if overridden
            if ($proposal->document_number_override && $proposal->isDirty('document_number_suffix')) {
                $date = $proposal->issue_date ? Carbon::parse($proposal->issue_date) : now();
                $proposal->document_number = DocumentNumberGenerator::regenerateWithSuffix(
                    'QUO',
                    $proposal->document_number_raw,
                    $date,
                    $proposal->document_number_suffix
                );
            }
        });
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
