<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Spatie\Translatable\HasTranslations;

class Company extends Model
{
    use HasFactory;
    use HasTranslations;
    use LogsModelActivity;

    protected $fillable = [
        'company_name', 'brand_name', 'logo', 'address',
        'email_1', 'email_2', 'phone_1', 'phone_2',
        'tax_id', 'website', 'google_maps_embed_url', 'default_currency',
        'color_primary', 'color_secondary',
        'footer_text', 'bank', 'pic',
    ];
    protected $translatable = [
        'footer_text',
    ];

    protected $casts = [
        'bank' => 'array',
        'pic'  => 'array',
    ];

    public function proposals()
    {
        return $this->hasMany(Proposal::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function googleMapsEmbedSrc(): ?string
    {
        $url = trim((string) ($this->google_maps_embed_url ?? ''));

        if ($url === '') {
            return null;
        }

        return cache()->remember(
            'company.google_maps_embed_src.' . sha1($url),
            now()->addDay(),
            fn (): ?string => self::buildGoogleMapsEmbedSrc($url),
        );
    }

    protected static function buildGoogleMapsEmbedSrc(string $url): ?string
    {
        if (preg_match('/src=["\']([^"\']+)["\']/i', $url, $matches) === 1) {
            return $matches[1];
        }

        if (str_contains($url, '/maps/embed')) {
            return $url;
        }

        $url = self::resolveGoogleMapsUrl($url);

        if (preg_match('/[?&]cid=(\d+)/', $url, $matches) === 1) {
            return 'https://www.google.com/maps?cid=' . $matches[1] . '&output=embed';
        }

        if (
            str_starts_with($url, 'https://www.google.com/maps')
            || str_starts_with($url, 'https://maps.google.com')
        ) {
            return str_contains($url, '?')
                ? $url . '&output=embed'
                : $url . '?output=embed';
        }

        return $url;
    }

    protected static function resolveGoogleMapsUrl(string $url): string
    {
        if (! preg_match('#^https?://(?:maps\.app\.goo\.gl|goo\.gl/maps)/#i', $url)) {
            return $url;
        }

        try {
            $response = Http::timeout(10)
                ->withOptions(['allow_redirects' => ['max' => 5]])
                ->get($url);

            $effectiveUri = $response->transferStats?->getEffectiveUri();

            if ($effectiveUri !== null) {
                return (string) $effectiveUri;
            }
        } catch (\Throwable) {
            return $url;
        }

        return $url;
    }
}
