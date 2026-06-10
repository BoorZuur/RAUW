<?php

namespace App\Support;

use Illuminate\Database\QueryException;

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
            if (self::isDuplicateConstraint($e)) {
                abort(409, 'feedback_already_submitted');
            }
            throw $e;
        }
    }

    private static function isDuplicateConstraint(QueryException $e): bool
    {
        $message = $e->getMessage();
        return str_contains($message, '1062 Duplicate entry') || 
               str_contains($message, 'UNIQUE constraint failed');
    }
}
