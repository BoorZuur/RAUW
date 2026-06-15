<?php

namespace App\Enums;

enum IssueMessageSenderType: string
{
    case User = 'user';
    case Officer = 'officer';
    case System = 'system';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
