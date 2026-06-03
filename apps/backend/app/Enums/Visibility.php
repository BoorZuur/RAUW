<?php

namespace App\Enums;

enum Visibility: string
{
    case Visible = 'visible';
    case Hidden = 'hidden';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
