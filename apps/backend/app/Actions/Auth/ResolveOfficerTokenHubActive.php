<?php

namespace App\Actions\Auth;

use App\Models\Officer;
use Illuminate\Support\Carbon;

class ResolveOfficerTokenHubActive
{
    /**
     * Derive hub-active state from the officer's shared shift clock.
     *
     * All devices and tokens for an officer share one shift via
     * {@see Officer::$hub_active_until}. Workflow access and profile responses
     * read this column (plus {@see Officer::$is_active}), not the bearer token's
     * Sanctum `hub-active` ability. Past timestamps are inactive even when the
     * column has not yet been normalized to null.
     *
     * @return array{hub_active: bool, hub_active_until: string|null}
     */
    public function resolve(Officer $officer): array
    {
        $hubActiveUntil = $officer->hub_active_until;

        $hubActive = $officer->is_active
            && $hubActiveUntil instanceof Carbon
            && $hubActiveUntil->isFuture();

        return [
            'hub_active' => $hubActive,
            'hub_active_until' => $hubActive ? $hubActiveUntil->toIso8601String() : null,
        ];
    }
}
