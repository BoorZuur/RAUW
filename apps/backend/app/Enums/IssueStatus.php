<?php

namespace App\Enums;

enum IssueStatus: string
{
    case Open = 'open';
    case InProgress = 'in_behandeling';
    case Resolved = 'opgelost';
    case Closed = 'gesloten';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Whether an officer may self-assign to an issue in this status.
     */
    public function isAssignable(): bool
    {
        return ! in_array($this, [self::Resolved, self::Closed], true);
    }

    /**
     * Whether an officer may create or update a resolution on an issue in this status.
     */
    public function isResolutionWritable(): bool
    {
        return $this !== self::Closed;
    }
}
