<?php

namespace App\Enums;

enum FlagAction: string
{
    case Pending = 'pending';
    case Dismissed = 'dismissed';
    case ContentHidden = 'content_hidden';
    case UserWarned = 'user_warned';
    case Escalated = 'escalated';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
