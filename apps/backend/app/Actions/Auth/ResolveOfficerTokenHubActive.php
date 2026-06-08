<?php

namespace App\Actions\Auth;

use App\Models\Officer;
use Illuminate\Support\Carbon;

class ResolveOfficerTokenHubActive
{
    /**
     * Derive hub-active state from the officer's shared shift clock.
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
