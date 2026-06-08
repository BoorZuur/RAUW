<?php

namespace App\Support;

use App\Enums\IssueStatus;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class IssueStatusTransition
{
    /**
     * Whether the directed officer status workflow allows this transition.
     */
    public static function canTransition(IssueStatus $from, IssueStatus $to): bool
    {
        if ($from === $to) {
            return false;
        }

        return match ($from) {
            IssueStatus::Open => $to === IssueStatus::InProgress,
            IssueStatus::InProgress => in_array($to, [IssueStatus::Resolved, IssueStatus::Closed], true),
            IssueStatus::Resolved => $to === IssueStatus::Closed,
            IssueStatus::Closed => false,
        };
    }

    /**
     * Assert the transition is valid or throw a validation exception.
     *
     * @throws ValidationException
     */
    public static function assertTransition(IssueStatus $from, IssueStatus $to): void
    {
        if (! self::canTransition($from, $to)) {
            throw ValidationException::withMessages([
                'status' => [
                    sprintf(
                        'Invalid status transition from %s to %s.',
                        $from->value,
                        $to->value,
                    ),
                ],
            ]);
        }
    }

    /**
     * Resolve the issue's resolved_at timestamp after a status transition.
     *
     * Set to now() on the first transition to opgelost; otherwise unchanged.
     */
    public static function resolvedAtForTransition(
        IssueStatus $from,
        IssueStatus $to,
        ?Carbon $currentResolvedAt,
    ): ?Carbon {
        if ($to === IssueStatus::Resolved && $currentResolvedAt === null) {
            return now();
        }

        return $currentResolvedAt;
    }
}
