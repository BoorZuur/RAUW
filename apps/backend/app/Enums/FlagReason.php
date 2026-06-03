<?php

namespace App\Enums;

enum FlagReason: string
{
    case BlockedKeyword = 'blocked_keyword';
    case Inappropriate = 'inappropriate';
    case Spam = 'spam';
    case Harassment = 'harassment';
    case PersonalData = 'personal_data';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
