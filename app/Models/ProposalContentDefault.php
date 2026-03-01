<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class ProposalContentDefault extends Model
{
    use HasTranslations;

    public const GLOBAL_FIELD_KEY = '__all__';

    public const FIELD_OPTIONS = [
        'brief' => 'Brief',
        'core_services' => 'Core Services',
        'features' => 'Features',
        'server' => 'Server',
        'assets' => 'Assets',
        'security' => 'Security',
        'support' => 'Support',
        'additional_benefit' => 'Additional Benefits',
        'add_on' => 'Add-ons',
        'payment' => 'Payment Terms',
        'terms_condition' => 'Terms & Conditions',
        'offer_1_project_timeline' => 'Offer 1 Project Timeline',
        'offer_2_project_timeline' => 'Offer 2 Project Timeline',
    ];

    protected $fillable = [
        'field_key',
        'value',
    ];

    protected $translatable = [
        'value',
    ];
}
