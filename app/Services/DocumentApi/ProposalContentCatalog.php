<?php

namespace App\Services\DocumentApi;

use App\Models\ProposalContentDefault;

class ProposalContentCatalog
{
    public const RICH_TEXT_FIELDS = [
        'brief',
        'extra_content_brief',
        'core_services',
        'features',
        'server',
        'assets',
        'security',
        'support',
        'additional_benefit',
        'payment',
        'terms_condition',
        'additional_info',
        'faq',
        'our_process',
        'about_us',
    ];

    public const TRANSLATABLE_REPEATER_FIELDS = [
        'add_on',
        'offer_1_project_timeline',
        'offer_2_project_timeline',
    ];

    public const SHARED_REPEATER_FIELDS = [
        'video_testimonials',
        'client_logos',
    ];

    public const TIMELINE_FIELDS = [
        'offer_1_project_timeline',
        'offer_2_project_timeline',
    ];

    public const TIMELINE_TEMPLATES = [
        'short_project_timeline',
        'business_project_timeline',
        'prime_project_timeline',
        'corporate_project_timeline',
        'custom_project_timeline',
    ];

    /**
     * @return array<int, string>
     */
    public static function fieldKeys(): array
    {
        return [
            ...self::RICH_TEXT_FIELDS,
            ...self::TRANSLATABLE_REPEATER_FIELDS,
            ...self::SHARED_REPEATER_FIELDS,
        ];
    }

    public static function isRichText(string $field): bool
    {
        return in_array($field, self::RICH_TEXT_FIELDS, true);
    }

    public static function isTranslatableRepeater(string $field): bool
    {
        return in_array($field, self::TRANSLATABLE_REPEATER_FIELDS, true);
    }

    public static function isSharedRepeater(string $field): bool
    {
        return in_array($field, self::SHARED_REPEATER_FIELDS, true);
    }

    public static function isTimeline(string $field): bool
    {
        return in_array($field, self::TIMELINE_FIELDS, true);
    }

    /**
     * @return array<int, string>
     */
    public static function allowedTemplateKeys(): array
    {
        return array_keys(ProposalContentDefault::FIELD_OPTIONS);
    }
}
