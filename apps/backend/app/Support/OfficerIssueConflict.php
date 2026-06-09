<?php

namespace App\Support;

use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class OfficerIssueConflict extends Exception implements HttpExceptionInterface
{
    public $code;

    public function __construct(
        string $code,
        string $message,
        public readonly int $status,
    ) {
        parent::__construct($message);
        $this->code = $code;
    }

    public function getStatusCode(): int
    {
        return $this->status;
    }

    public function getHeaders(): array
    {
        return [];
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

    public static function notUpdateAuthor(): self
    {
        return new self(
            code: 'not_update_author',
            message: 'Only the officer who authored this update may modify or delete it.',
            status: Response::HTTP_FORBIDDEN,
        );
    }

    public static function issueClosed(?string $message = null): self
    {
        return new self(
            code: 'issue_closed',
            message: $message ?? 'Cannot create or update a resolution on a closed issue.',
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }
}
