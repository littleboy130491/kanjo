<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Proposal extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    protected $translatable = [
        'brief', 'core_services', 'features', 'server', 'assets',
        'security', 'support', 'additional_benefit', 'add_on',
        'payment', 'terms_condition',
        'offer_1_project_timeline', 'offer_2_project_timeline',
    ];

    protected $casts = [
        'portfolios'               => 'array',
        'document_number_override' => 'boolean',
        'issue_date'               => 'date',
        'valid_until'              => 'date',
        'tax_rate'                 => 'decimal:2',
        'tax_amount'               => 'decimal:2',
        'total_amount'             => 'decimal:2',
        'offer_1_price'            => 'decimal:2',
        'offer_1_original_price'   => 'decimal:2',
        'offer_1_renewal_price'    => 'decimal:2',
        'offer_2_price'            => 'decimal:2',
        'offer_2_original_price'   => 'decimal:2',
        'offer_2_renewal_price'    => 'decimal:2',
    ];

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
}
