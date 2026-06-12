<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class ProposalContentDefault extends Model
{
    use LogsModelActivity;
    use HasTranslations;

    public const GLOBAL_FIELD_KEY = '__all__';

    public const FIELD_OPTIONS = [
        'brief' => 'Brief',
        'extra_content_brief' => 'Extra Content Brief',
        'core_services' => 'Core Services',
        'features' => 'Features',
        'ecommerce_features' => 'E-commerce Features',
        'server' => 'Server',
        'assets' => 'Assets',
        'security' => 'Security',
        'support' => 'Support',
        'add_on' => 'Add-ons',
        'additional_benefit' => 'Additional Benefits',
        'short_project_timeline' => 'Short Project Timeline',
        'business_project_timeline' => 'Business Project Timeline',
        'prime_project_timeline' => 'Prime Project Timeline',
        'corporate_project_timeline' => 'Corporate Project Timeline',
        'custom_project_timeline' => 'Custom Project Timeline',
        'payment' => 'Payment Terms',
        'terms_condition' => 'Terms & Conditions',
        'additional_info' => 'Additional Info',
        'faq' => 'FAQ',
        'our_process' => 'Our Process',
        'about_us' => 'About Us',
        'video_testimonials' => 'Video Testimonials',
        'client_logos' => 'Client Logos',
        'marketing_program' => 'Marketing Program',
    ];

    protected $fillable = [
        'field_key',
        'value',
    ];

    protected $translatable = [
        'value',
    ];
}
