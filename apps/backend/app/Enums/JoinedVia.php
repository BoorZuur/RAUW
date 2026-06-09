<?php

namespace App\Enums;

enum JoinedVia: string
{
    case Creator = 'creator';
    case Manual = 'manual';
    case Duplicate = 'duplicate';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
