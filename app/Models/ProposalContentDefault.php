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

    public const SHARED_JSON_REPEATER_FIELDS = [
        'video_testimonials',
        'client_logos',
    ];

    protected $fillable = [
        'field_key',
        'value',
    ];

    protected $translatable = [
        'value',
    ];

    /**
     * @param  array<string, mixed>  $translations
     * @return array<int, array<string, mixed>>
     */
    public static function resolveSharedJsonRepeaterValue(array $translations, string $fieldKey): array
    {
        if (! in_array($fieldKey, self::SHARED_JSON_REPEATER_FIELDS, true)) {
            return [];
        }

        $locales = array_unique(array_filter([
            config('app.locale', 'en'),
            config('app.fallback_locale', 'en'),
            ...config('translatable.locales', ['en', 'id']),
        ]));

        foreach ($locales as $locale) {
            $value = data_get($translations, "{$locale}.{$fieldKey}");

            if (is_array($value)) {
                return $value;
            }
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $translations
     * @return array<string, mixed>
     */
    public static function syncSharedJsonRepeaterFields(array $translations): array
    {
        $locales = config('translatable.locales', ['en', 'id']);

        foreach (self::SHARED_JSON_REPEATER_FIELDS as $fieldKey) {
            $sharedValue = self::resolveSharedJsonRepeaterValue($translations, $fieldKey);

            foreach ($locales as $locale) {
                data_set($translations, "{$locale}.{$fieldKey}", $sharedValue);
            }
        }

        return $translations;
    }
}
