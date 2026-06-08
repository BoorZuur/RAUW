<?php

namespace App\Actions\Auth;

use App\Models\Officer;
use Laravel\Sanctum\PersonalAccessToken;

class CloseOfficerSessions
{
    public function closeAllFor(Officer $officer): void
    {
        $officer->sessions()
            ->where('is_active', true)
            ->update([
                'shift_end' => now(),
                'is_active' => false,
            ]);
    }

    public function closeForToken(Officer $officer, PersonalAccessToken $token): void
    {
        $session = $officer->sessions()
            ->where('personal_access_token_id', $token->id)
            ->where('is_active', true)
            ->first();

        if ($session === null) {
            return;
        }

        $session->update([
            'shift_end' => now(),
            'is_active' => false,
        ]);
    }
}
