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

    protected static function booted(): void
    {
        static::saved(function (Company $company): void {
            foreach ([
                $company->google_maps_embed_url,
                $company->getOriginal('google_maps_embed_url'),
            ] as $url) {
                $url = trim((string) ($url ?? ''));

                if ($url !== '') {
                    cache()->forget('company.google_maps_embed_src.' . sha1($url));
                }
            }
        });
    }

    public function proposals()
    {
        return $this->hasMany(Proposal::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function googleMapsLink(): ?string
    {
        $url = trim((string) ($this->google_maps_embed_url ?? ''));

        if ($url === '') {
            return null;
        }

        if (preg_match('/src=["\']([^"\']+)["\']/i', $url, $matches) === 1) {
            return $matches[1];
        }

        if (str_contains($url, '/maps/embed')) {
            return $this->resolveGoogleMapsUrl($url);
        }

        return $this->resolveGoogleMapsUrl($url);
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

        $resolvedUrl = self::resolveGoogleMapsUrlStatic($url);

        if ($embedSrc = self::fetchPlaceEmbedSrcFromPage($resolvedUrl)) {
            return $embedSrc;
        }

        if ($embedSrc = self::buildPlaceEmbedSrcFromUrl($resolvedUrl)) {
            return $embedSrc;
        }

        return null;
    }

    protected static function fetchPlaceEmbedSrcFromPage(string $url): ?string
    {
        if (! str_contains($url, 'google.com/maps')) {
            return null;
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; Kanjo/1.0; +https://github.com/littleboy130491/kanjo)',
                    'Accept-Language' => 'en-US,en;q=0.9',
                ])
                ->withOptions(['allow_redirects' => ['max' => 5]])
                ->get($url);

            if (! $response->successful()) {
                return null;
            }

            if (! preg_match('/pb=([^&"\']+)/', $response->body(), $matches)) {
                return null;
            }

            $pb = urldecode($matches[1]);

            if (! str_starts_with($pb, '!1m')) {
                return null;
            }

            return 'https://www.google.com/maps/embed?pb=' . $pb;
        } catch (\Throwable) {
            return null;
        }
    }

    protected static function buildPlaceEmbedSrcFromUrl(string $url): ?string
    {
        if (! str_contains($url, '/maps/place/')) {
            return null;
        }

        if (! preg_match('#/place/([^/@]+)/@(-?\d+\.?\d*),(-?\d+\.?\d*),(\d+)z#', $url, $matches)) {
            return null;
        }

        $placeName = rawurlencode(str_replace('+', ' ', urldecode($matches[1])));
        $lat = $matches[2];
        $lng = $matches[3];
        $zoom = max(1, (int) $matches[4]);

        if (! preg_match('/1s([^!]+)/', $url, $placeMatches)) {
            return null;
        }

        $placeId = rawurlencode(urldecode($placeMatches[1]));

        if (preg_match('/3d(-?\d+\.?\d*)!4d(-?\d+\.?\d*)/', $url, $coordinateMatches)) {
            $lat = $coordinateMatches[1];
            $lng = $coordinateMatches[2];
        }

        $scale = 591657550.5 / (2 ** ($zoom + 1));

        $pb = sprintf(
            '!1m18!1m12!1m3!1d%s!2d%s!3d%s!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s%s!2s%s!5e0!3m2!1sen!2sid!4v%d!5m2!1sen!2sid',
            $scale,
            $lng,
            $lat,
            $placeId,
            $placeName,
            now()->getTimestamp() * 1000,
        );

        return 'https://www.google.com/maps/embed?pb=' . $pb;
    }

    protected function resolveGoogleMapsUrl(string $url): string
    {
        return self::resolveGoogleMapsUrlStatic($url);
    }

    protected static function resolveGoogleMapsUrlStatic(string $url): string
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
