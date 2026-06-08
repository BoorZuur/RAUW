<?php

namespace App\Actions\Auth;

use App\Enums\OfficerHubLoginEligibility;

readonly class OfficerHubLoginEvaluation
{
    public function __construct(
        public OfficerHubLoginEligibility $eligibility,
        public ?int $distanceMeters = null,
    ) {
    }

    public function isHubActiveEligible(): bool
    {
        return $this->eligibility->isHubActiveEligible();
    }
}
