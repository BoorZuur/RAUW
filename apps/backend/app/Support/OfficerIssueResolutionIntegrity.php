<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;

class OfficerIssueResolutionIntegrity
{
    /**
     * Whether a query exception reflects a duplicate officer_issue_resolutions.issue_id.
     */
    public static function isDuplicateIssueIdViolation(QueryException $exception): bool
    {
        if (! ($exception instanceof UniqueConstraintViolationException) && ! self::isIntegrityConstraint($exception)) {
            return false;
        }

        return self::targetsOfficerIssueResolutions($exception);
    }

    private static function isIntegrityConstraint(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;

        if ($sqlState !== null && str_starts_with((string) $sqlState, '23')) {
            return true;
        }

        return in_array($exception->getCode(), ['23000', '23505', '1062'], true);
    }

    private static function targetsOfficerIssueResolutions(QueryException $exception): bool
    {
        $sql = $exception->getSql();

        if (is_string($sql) && $sql !== '' && str_contains(strtolower($sql), 'officer_issue_resolutions')) {
            return true;
        }

        $message = strtolower($exception->getMessage());

        if (str_contains($message, 'officer_issue_resolutions_issue_id_unique')) {
            return true;
        }

        return str_contains($message, 'officer_issue_resolutions')
            && str_contains($message, 'issue_id');
    }
}
