<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\DocumentView;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Throwable;

class DocumentViewLogger
{
    public static function record(Model $document, string $pageType = 'html'): void
    {
        try {
            if (self::isStaffViewer()) {
                return;
            }

            $request = request();

            $userAgent = (string) $request->userAgent('');

            DocumentView::query()->create([
                'viewable_type' => $document::class,
                'viewable_id' => $document->getKey(),
                'viewed_at' => Carbon::now(),
                'page_type' => $pageType,
                'ip_address' => $request->ip(),
                'user_agent' => $userAgent ?: null,
                'browser' => self::parseBrowser($userAgent),
                'platform' => self::parsePlatform($userAgent),
                'device' => self::parseDevice($userAgent),
                'referer' => $request->headers->get('referer'),
            ]);
        } catch (Throwable) {
        }
    }

    private static function isStaffViewer(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->hasAnyRole([
            UserRole::SuperAdmin->value,
            UserRole::Editor->value,
        ]);
    }

    private static function parseBrowser(string $userAgent): ?string
    {
        $browsers = [
            'Edge' => 'Edge|Edg/',
            'Opera' => 'Opera|OPR/',
            'Samsung Internet' => 'SamsungBrowser',
            'Chrome' => 'Chrome|CriOS',
            'Firefox' => 'Firefox|FxiOS',
            'Safari' => 'Safari',
        ];

        foreach ($browsers as $name => $pattern) {
            if (preg_match("#{$pattern}#i", $userAgent)) {
                if ($name === 'Chrome' && preg_match('#(Edge|Edg/|Opera|OPR/|SamsungBrowser)#i', $userAgent)) {
                    continue;
                }

                if ($name === 'Safari' && preg_match('#(Chrome|CriOS|FxiOS|Firefox|Edge|Edg/|Opera|OPR/|SamsungBrowser)#i', $userAgent)) {
                    continue;
                }

                return $name;
            }
        }

        return $userAgent === '' ? null : 'Other';
    }

    private static function parsePlatform(string $userAgent): ?string
    {
        $platforms = [
            'Windows' => 'Windows',
            'macOS' => 'Macintosh|Mac OS',
            'Android' => 'Android',
            'iOS' => 'iPhone|iPad|iPod',
            'Linux' => 'Linux',
        ];

        foreach ($platforms as $name => $pattern) {
            if (preg_match("#{$pattern}#i", $userAgent)) {
                return $name;
            }
        }

        return $userAgent === '' ? null : 'Other';
    }

    private static function parseDevice(string $userAgent): ?string
    {
        if ($userAgent === '') {
            return null;
        }

        if (preg_match('#Tablet|iPad#i', $userAgent)) {
            return 'Tablet';
        }

        if (preg_match('#Mobile|iPhone|iPod|Android.*Mobile#i', $userAgent)) {
            return 'Mobile';
        }

        return 'Desktop';
    }
}
