<?php

namespace App\Actions\Auth;

use App\Models\Officer;

class CloseOfficerSessions
{
    public function closeFor(Officer $officer): void
    {
        $officer->sessions()
            ->where('is_active', true)
            ->update([
                'shift_end' => now(),
                'is_active' => false,
            ]);
    }
}
