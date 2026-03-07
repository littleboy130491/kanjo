<?php

namespace App\Support;

use Carbon\Carbon;

class ServiceDate
{
    public static function format(?string $value, bool $includeYear = true): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('Y-m-d', $value);
        } catch (\Throwable) {
            return $value;
        }

        return $date->translatedFormat($includeYear ? 'F j, Y' : 'F j');
    }
}
