<?php

namespace App\Support\Issues;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class IssueDuplicateConflict extends Exception
{
    public function __construct(
        private readonly string $conflictCode,
        string $message,
        public readonly int $status,
    ) {
        parent::__construct($message);
    }

    /**
     * Structured API error code (avoids clashing with Exception::$code on PHP 8.4+).
     */
    public function __get(string $name): mixed
    {
        if ($name === 'code') {
            return $this->conflictCode;
        }

        throw new \Error(sprintf('Undefined property %s::$%s', static::class, $name));
    }

    public static function issueNotCanonical(
        string $message = 'The target issue is not a canonical issue.',
    ): self {
        return new self(
            conflictCode: 'issue_not_canonical',
            message: $message,
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function issueNotMatchable(
        string $message = 'The target issue is not open for duplicate linking.',
    ): self {
        return new self(
            conflictCode: 'issue_not_matchable',
            message: $message,
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function duplicateTargetNotFound(
        string $message = 'The duplicate target issue was not found.',
    ): self {
        return new self(
            conflictCode: 'duplicate_target_not_found',
            message: $message,
            status: Response::HTTP_NOT_FOUND,
        );
    }

    public static function notParticipant(
        string $message = 'You are not a participant on this issue.',
    ): self {
        return new self(
            conflictCode: 'not_participant',
            message: $message,
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function cannotJoinDuplicateChild(
        string $message = 'You must join the canonical issue, not a duplicate child.',
    ): self {
        return new self(
            conflictCode: 'cannot_join_duplicate_child',
            message: $message,
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function cannotDuplicateSelf(
        string $message = 'You cannot link a duplicate to your own issue.',
    ): self {
        return new self(
            conflictCode: 'cannot_duplicate_self',
            message: $message,
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function issueIsDuplicateChild(
        string $message = 'This operation requires a canonical issue, not a duplicate child.',
    ): self {
        return new self(
            conflictCode: 'issue_is_duplicate_child',
            message: $message,
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function issueNotLinkable(
        string $message = 'The issue cannot be linked as a duplicate in its current state.',
    ): self {
        return new self(
            conflictCode: 'issue_not_linkable',
            message: $message,
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function issueHasDuplicates(
        string $message = 'The issue has duplicate children and cannot be linked as a duplicate.',
    ): self {
        return new self(
            conflictCode: 'issue_has_duplicates',
            message: $message,
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }
}
