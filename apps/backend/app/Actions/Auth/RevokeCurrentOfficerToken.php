<?php

namespace App\Actions\Auth;

use App\Models\Officer;

class RevokeCurrentOfficerToken
{
    public function revoke(Officer $officer): void
    {
        $officer->currentAccessToken()?->delete();
    }
}
