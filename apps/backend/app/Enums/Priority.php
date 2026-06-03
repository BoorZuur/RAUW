<?php

namespace App\Enums;

enum Priority: string
{
    case Low = 'laag';
    case Medium = 'midden';
    case High = 'zwaar';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
