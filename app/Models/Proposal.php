<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Models\Concerns\HasDocumentModelBehavior;
use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Proposal extends Model
{
    use HasDocumentModelBehavior;
    use HasFactory;
    use LogsModelActivity;
    use HasTranslations;
    use SoftDeletes;

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
        'additional_info',
        'extra_content_brief',
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
        'access_credentials_updated_at',
        'notes',
        'user_id',
        'updated_by',
        'company_id',
        'client_id',
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
        'additional_info',
        'extra_content_brief',
        'offer_1_project_timeline',
        'offer_2_project_timeline',
    ];

    protected $casts = [
        'document_number_override' => 'boolean',
        'issue_date' => 'date',
        'valid_until' => 'date',
        'access_credentials_updated_at' => 'datetime',
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

    protected static function documentNumberType(): string
    {
        return 'QUO';
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

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function service(): HasOneThrough
    {
        return $this->hasOneThrough(
            Service::class,
            Invoice::class,
            'proposal_id',
            'id',
            'id',
            'service_id',
        );
    }

    public function portfolios()
    {
        return $this->belongsToMany(Portfolio::class);
    }

    /**
     * @return array<int, string>
     */
    protected function activityLogExceptAttributes(): array
    {
        return array_merge([
            'created_at',
            'updated_at',
            'deleted_at',
        ], [
            'access_password',
        ]);
    }

    protected function activityLogLevel(): string
    {
        $level = config('activitylog.document_log_level', 'detailed');

        return in_array($level, ['detailed', 'normal', 'simple'], true)
            ? $level
            : 'detailed';
    }

    /**
     * @return array<int, string>
     */
    protected function activityLogSimpleAttributes(): array
    {
        return [
            'document_number',
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
            'offer_name_1',
            'offer_1_price',
            'offer_1_renewal_price',
            'offer_name_2',
            'offer_2_price',
            'offer_2_renewal_price',
            'status',
            'company_id',
            'client_id',
            'user_id',
            'updated_by',
        ];
    }
}
