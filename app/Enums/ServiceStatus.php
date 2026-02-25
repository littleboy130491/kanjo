<?php

namespace App\Enums;

enum ServiceStatus: string
{
    case TERMINATED = 'terminated';
    case ON_GOING = 'on-going';
    case SUSPENDED = 'suspended';

    public function getLabel(): string
    {
        return match ($this) {
            self::TERMINATED => 'Terminated',
            self::ON_GOING => 'On Going',
            self::SUSPENDED => 'Suspended',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::TERMINATED => 'danger',
            self::ON_GOING => 'success',
            self::SUSPENDED => 'warning',
        };
    }
}