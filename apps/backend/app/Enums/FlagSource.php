<?php

namespace App\Enums;

enum FlagSource: string
{
    case Keyword = 'keyword';
    case Manual = 'manual';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
