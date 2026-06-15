<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;

class IssueFeedbackIntegrity
{
    /**
     * Wrap execution and catch duplicate feedback exception.
     */
    public static function wrap(callable $callback)
    {
        try {
            return $callback();
        } catch (QueryException $e) {
            if (self::isDuplicateReviewerViolation($e)) {
                abort(409, 'feedback_already_submitted');
            }
            throw $e;
        }
    }

    /**
     * Whether a query exception reflects a duplicate issue_feedback (issue_id, reviewer_user_id).
     */
    public static function isDuplicateReviewerViolation(QueryException $exception): bool
    {
        if (! ($exception instanceof UniqueConstraintViolationException) && ! self::isIntegrityConstraint($exception)) {
            return false;
        }

        return self::targetsIssueFeedback($exception);
    }

    private static function isIntegrityConstraint(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;

        if ($sqlState !== null && str_starts_with((string) $sqlState, '23')) {
            return true;
        }

        return in_array($exception->getCode(), ['23000', '23505', '1062'], true);
    }

    private static function targetsIssueFeedback(QueryException $exception): bool
    {
        $sql = $exception->getSql();

        if (is_string($sql) && $sql !== '' && str_contains(strtolower($sql), 'issue_feedback')) {
            return true;
        }

        $message = strtolower($exception->getMessage());

        if (str_contains($message, 'issue_feedback_issue_id_reviewer_user_id_unique')) {
            return true;
        }

        return str_contains($message, 'issue_feedback')
            && str_contains($message, 'reviewer_user_id');
    }
}
