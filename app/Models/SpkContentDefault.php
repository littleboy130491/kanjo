<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class SpkContentDefault extends Model
{
    use HasTranslations;
    use LogsModelActivity;

    public const GLOBAL_FIELD_KEY = '__all__';

    public const FIELD_OPTIONS = [
        'title' => 'Title',
        'subject' => 'Subject',
        'content' => 'Content',
    ];

    protected $fillable = [
        'field_key',
        'value',
    ];

    protected $translatable = [
        'value',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function globalDefaults(string $locale): array
    {
        $record = self::query()
            ->where('field_key', self::GLOBAL_FIELD_KEY)
            ->first();

        if (! $record) {
            return [];
        }

        $translations = $record->getTranslations('value');

        return data_get($translations, $locale)
            ?? data_get($translations, config('app.fallback_locale', 'en'))
            ?? [];
    }
}
