<?php

declare(strict_types=1);

namespace App\Support;

use Awcodes\Curator\Concerns\UrlProvider;
use Awcodes\Curator\Glide\GlideBuilder;

class CuratorUrlProvider implements UrlProvider
{
    public static function getThumbnailUrl(string $path): string
    {
        return GlideBuilder::make()
            ->width(200)
            ->height(200)
            ->fit('crop')
            ->toUrl($path);
    }

    public static function getMediumUrl(string $path): string
    {
        return GlideBuilder::make()
            ->width(640)
            ->height(640)
            ->fit('crop')
            ->toUrl($path);
    }

    public static function getLargeUrl(string $path): string
    {
        return GlideBuilder::make()
            ->width(1024)
            ->height(1024)
            ->fit('contain')
            ->toUrl($path);
    }
}
