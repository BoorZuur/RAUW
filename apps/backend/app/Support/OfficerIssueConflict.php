<?php

namespace App\Support;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class OfficerIssueConflict extends Exception
{
    public function __construct(
        public readonly string $code,
        string $message,
        public readonly int $status,
    ) {
        parent::__construct($message);
    }

    public static function issueAlreadyAssigned(): self
    {
        return new self(
            code: 'issue_already_assigned',
            message: 'Issue is already assigned to another officer.',
            status: Response::HTTP_CONFLICT,
        );
    }

    public static function notAssignedOfficer(string $message = 'Only the assigned officer may perform this action.'): self
    {
        return new self(
            code: 'not_assigned_officer',
            message: $message,
            status: Response::HTTP_FORBIDDEN,
        );
    }

    public static function officerResolutionExists(): self
    {
        return new self(
            code: 'officer_resolution_exists',
            message: 'An officer resolution already exists for this issue.',
            status: Response::HTTP_CONFLICT,
        );
    }

    public static function issueNotAssignable(): self
    {
        return new self(
            code: 'issue_not_assignable',
            message: 'Issues that are resolved or closed cannot be assigned.',
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }
}
