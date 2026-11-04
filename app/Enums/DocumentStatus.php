<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    /** Proposals only. Invoices and SPKs never use it. */
    case EXPIRED = 'expired';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Published',
            self::EXPIRED => 'Expired',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PUBLISHED => 'success',
            self::EXPIRED => 'danger',
        };
    }

    /**
     * Statuses available to invoices and SPKs.
     *
     * @return array<int, self>
     */
    public static function invoiceCases(): array
    {
        return [self::DRAFT, self::PUBLISHED];
    }

    /**
     * @param  array<int, self>|null  $cases
     * @return array<string, string>
     */
    public static function options(?array $cases = null): array
    {
        return collect($cases ?? self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->getLabel()])
            ->all();
    }
}
