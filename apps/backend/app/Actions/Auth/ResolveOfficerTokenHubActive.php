<?php

namespace App\Actions\Auth;

use App\Models\Officer;
use Illuminate\Support\Carbon;

class ResolveOfficerTokenHubActive
{
    /**
     * Derive hub-active state for the current bearer token.
     *
     * Matches {@see EnsureOfficerHubActive} and the login response contract:
     * the token must carry the `hub-active` ability, the officer must be active,
     * and `hub_active_until` must still be in the future.
     *
     * @return array{hub_active: bool, hub_active_until: string|null}
     */
    public function resolve(Officer $officer): array
    {
        $hubActiveUntil = $officer->hub_active_until;

        $hubActive = $officer->is_active
            && $officer->tokenCan('hub-active')
            && $hubActiveUntil instanceof Carbon
            && $hubActiveUntil->isFuture();

        return [
            'hub_active' => $hubActive,
            'hub_active_until' => $hubActive ? $hubActiveUntil->toIso8601String() : null,
        ];
    }
}
