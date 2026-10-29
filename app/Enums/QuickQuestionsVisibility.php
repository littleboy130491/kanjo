<?php

namespace App\Enums;

enum QuickQuestionsVisibility: string
{
    case DEFAULT = 'default';
    case ON = 'on';
    case OFF = 'off';

    public function getLabel(): string
    {
        return match ($this) {
            self::DEFAULT => 'Follow settings',
            self::ON => 'Always show',
            self::OFF => 'Always hide',
        };
    }
}
