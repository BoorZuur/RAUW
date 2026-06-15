<?php

namespace App\Enums;

enum IssueMessageType: string
{
    case Message = 'message';
    case System = 'system';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
