<?php

namespace App\Actions\Auth;

use Illuminate\Support\Carbon;

readonly class OfficerAuthTokenResult
{
    public function __construct(
        public string $plainTextToken,
        public bool $hubActive,
        public ?Carbon $hubActiveUntil,
    ) {
    }
}
