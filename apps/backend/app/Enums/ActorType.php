<?php

namespace App\Enums;

enum ActorType: string
{
    case User = 'user';
    case Officer = 'officer';
    case Manager = 'manager';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return list<string>
     */
    public static function issueParticipantValues(): array
    {
        return [self::User->value, self::Officer->value];
    }

    /**
     * @return list<string>
     */
    public static function commentAuthorValues(): array
    {
        return [self::User->value, self::Officer->value, self::Manager->value];
    }

    /**
     * @return list<string>
     */
    public static function recipientValues(): array
    {
        return [self::User->value, self::Officer->value, self::Manager->value];
    }
}
