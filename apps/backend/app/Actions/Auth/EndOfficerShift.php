<?php

namespace App\Actions\Auth;

use App\Models\Officer;

class EndOfficerShift
{
    public function end(Officer $officer): void
    {
        $officer->hub_active_until = null;
        $officer->save();
    }
}
