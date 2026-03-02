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
        'extra_content_brief' => 'Extra Content Brief',
        'core_services' => 'Core Services',
        'features' => 'Features',
        'server' => 'Server',
        'assets' => 'Assets',
        'security' => 'Security',
        'support' => 'Support',
        'add_on' => 'Add-ons',
        'additional_benefit' => 'Additional Benefits',
        'offer_1_project_timeline' => 'Offer 1 Project Timeline',
        'offer_2_project_timeline' => 'Offer 2 Project Timeline',
        'payment' => 'Payment Terms',
        'terms_condition' => 'Terms & Conditions',
        'additional_info' => 'Additional Info',
    ];

    protected $fillable = [
        'field_key',
        'value',
    ];

    protected $translatable = [
        'value',
    ];
}
