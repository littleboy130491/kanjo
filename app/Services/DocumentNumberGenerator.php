<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DocumentNumberGenerator
{
    private static array $roman = [
        1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV',
        5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII',
        9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
    ];

    public static function generate(string $type, Carbon $date, ?string $suffix = 'NEW'): array
    {
        $suffix ??= 'NEW';

        return DB::transaction(function () use ($type, $date, $suffix) {
            $tableName = match ($type) {
                'QUO' => 'proposals',
                'INV' => 'invoices',
                'SPK' => 'spks',
                default => throw new \InvalidArgumentException("Unsupported document number type [{$type}]."),
            };
            
            // Get the next raw number for this type, month, and year
            $maxRaw = DB::table($tableName)
                ->selectRaw('MAX(document_number_raw) as max_raw')
                ->where('issue_month', $date->month)
                ->where('issue_year', $date->year)
                ->lockForUpdate()
                ->value('max_raw');

            $raw = $maxRaw ? $maxRaw + 1 : 1;
            $roman = self::toRoman($date->month);
            $yy = $date->format('y');
            
            $documentNumber = sprintf('%s/%03d/%s/%02d/%s', $type, $raw, $roman, $yy, $suffix);

            return [
                'document_number' => $documentNumber,
                'document_number_raw' => $raw,
            ];
        });
    }

    public static function toRoman(int $month): string
    {
        return self::$roman[$month] ?? '';
    }

    public static function regenerate(string $type, int $raw, Carbon $date, ?string $suffix = 'NEW'): string
    {
        $suffix ??= 'NEW';

        $roman = self::toRoman($date->month);
        $yy = $date->format('y');

        return sprintf('%s/%03d/%s/%02d/%s', $type, $raw, $roman, $yy, $suffix);
    }
}
