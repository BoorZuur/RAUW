<?php

namespace App\Actions\Auth;

use Illuminate\Support\Carbon;

readonly class StartOfficerShiftResult
{
    public function __construct(
        public bool $started,
        public ?Carbon $hubActiveUntil,
    ) {
    }
}
