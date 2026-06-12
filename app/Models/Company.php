<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

        if (preg_match('/src=["\']([^"\']+)["\']/i', $url, $matches) === 1) {
            return $matches[1];
        }

        if (str_contains($url, '/maps/embed')) {
            return $url;
        }

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
}
