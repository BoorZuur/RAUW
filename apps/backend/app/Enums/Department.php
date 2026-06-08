<?php

namespace App\Enums;

enum Department: string
{
    case DistrictManagement = 'wijkbeheer';
    case BoaYouth = 'boa_jeugd';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
