<?php

namespace App\Actions\Auth;

use App\Models\Officer;

/**
 * Full revoke for the officer-disable path: ends the shared shift and deletes
 * all Sanctum tokens. Do not use for logout — use RevokeCurrentOfficerToken.
 */
class RevokeOfficerHubActive
{
    public function __construct(
        private readonly EndOfficerShift $endOfficerShift,
    ) {
    }

    public function revoke(Officer $officer): void
    {
        $this->endOfficerShift->end($officer);

        $officer->tokens()->delete();
    }
}
