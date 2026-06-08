<?php

namespace App\Actions\Auth;

use App\Models\Officer;

class RevokeOfficerHubActive
{
    public function revoke(Officer $officer): void
    {
        $officer->hub_active_until = null;
        $officer->save();

        $officer->tokens()->delete();
    }
}
