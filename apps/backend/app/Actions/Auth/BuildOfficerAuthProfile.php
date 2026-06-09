<?php

namespace App\Actions\Auth;

use App\Http\Resources\AuthProfileResource;
use App\Models\Officer;
use Illuminate\Http\Request;

class BuildOfficerAuthProfile
{
    public function __construct(
        private readonly ResolveOfficerTokenHubActive $resolveOfficerTokenHubActive,
    ) {
    }

    /**
     * Build the canonical officer auth profile payload: identity fields from
     * {@see AuthProfileResource} merged with shared-shift hub state.
     *
     * @return array<string, mixed>
     */
    public function build(Officer $officer, Request $request): array
    {
        return array_merge(
            (new AuthProfileResource($officer))->toArray($request),
            $this->resolveOfficerTokenHubActive->resolve($officer),
        );
    }
}
