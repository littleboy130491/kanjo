<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class ProposalContentDefault extends Model
{
    use HasTranslations;
    use LogsModelActivity;

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
        'name',
        'slug',
        'is_default',
        'field_key',
        'value',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    protected $translatable = [
        'value',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $record): void {
            if (blank($record->name)) {
                $record->name = $record->field_key === self::GLOBAL_FIELD_KEY
                    ? 'Default'
                    : (string) ($record->name ?: 'Untitled');
            }

            if (blank($record->slug)) {
                $record->slug = Str::slug((string) $record->name) ?: 'pack';
            }

            $base = (string) $record->slug;
            $slug = $base;
            $suffix = 2;

            while (static::query()
                ->when($record->exists, fn ($query) => $query->whereKeyNot($record->getKey()))
                ->where('slug', $slug)
                ->exists()
            ) {
                $slug = $base.'-'.$suffix;
                $suffix++;
            }

            $record->slug = $slug;

            if (blank($record->field_key) || $record->field_key === self::GLOBAL_FIELD_KEY) {
                $record->field_key = $record->slug === 'default'
                    ? self::GLOBAL_FIELD_KEY
                    : $record->slug;
            }

            if ($record->is_default) {
                static::query()
                    ->when($record->exists, fn ($query) => $query->whereKeyNot($record->getKey()))
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });

        static::saved(function (self $record): void {
            if (static::query()->where('is_default', true)->exists()) {
                return;
            }

            static::query()->whereKey($record->getKey())->update(['is_default' => true]);
        });
    }

    public static function defaultPack(): ?self
    {
        return static::query()->where('is_default', true)->first()
            ?? static::query()->where('field_key', self::GLOBAL_FIELD_KEY)->first()
            ?? static::query()->orderBy('id')->first();
    }

    public static function pack(?int $id): ?self
    {
        if (filled($id)) {
            $record = static::query()->find($id);

            if ($record instanceof self) {
                return $record;
            }
        }

        return static::defaultPack();
    }

    /**
     * @return array<int, string>
     */
    public static function options(): array
    {
        return static::query()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (self $record): array => [
                $record->id => $record->is_default ? $record->name.' (Default)' : $record->name,
            ])
            ->all();
    }

    public function fieldValue(string $fieldKey, string $locale): mixed
    {
        $translations = $this->getTranslations('value');

        if (in_array($fieldKey, self::SHARED_JSON_REPEATER_FIELDS, true)) {
            return self::resolveSharedJsonRepeaterValue($translations, $fieldKey);
        }

        $value = data_get($translations, "{$locale}.{$fieldKey}");

        if ($value !== null) {
            return $value;
        }

        return data_get($this->getAttribute("value_{$locale}"), $fieldKey);
    }

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
