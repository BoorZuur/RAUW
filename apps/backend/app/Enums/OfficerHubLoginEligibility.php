<?php

namespace App\Enums;

enum OfficerHubLoginEligibility
{
    case HubActiveEligible;
    case OutsideRadius;
    case InactiveHub;
    case MissingCoordinates;
    case MissingLoginCoordinates;

    public function isHubActiveEligible(): bool
    {
        return $this === self::HubActiveEligible;
    }
}
