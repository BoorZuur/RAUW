<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * Rejects an email that already exists in any actor table (`users`,
 * `officers`, or `managers`).
 *
 * Login credentials are shared across all three actor tables, so an email
 * that is unique within a single table can still create cross-table login
 * ambiguity. This rule checks the raw table columns (including soft-deleted
 * rows) so that schema-level unique indexes are respected and reserved
 * identifiers cannot be reused.
 *
 * Optional `$ignoreTable` and `$ignoreId` allow profile updates to keep the
 * actor's current email or move to an unused address without a false positive
 * on the actor's own row.
 */
class UniqueActorEmail implements ValidationRule
{
    /**
     * Actor tables whose `email` column participates in shared login.
     *
     * @var list<string>
     */
    private const ACTOR_TABLES = ['users', 'officers', 'managers'];

    public function __construct(
        private ?string $ignoreTable = null,
        private ?int $ignoreId = null,
    ) {}

    /**
     * @param  Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        foreach (self::ACTOR_TABLES as $table) {
            $query = DB::table($table)->where('email', $value);

            if ($this->ignoreTable !== null && $this->ignoreId !== null && $table === $this->ignoreTable) {
                $query->where('id', '!=', $this->ignoreId);
            }

            if ($query->exists()) {
                $fail('The :attribute has already been taken.');

                return;
            }
        }
    }
}
