<?php

namespace App\Http\Resources;

use App\Models\Hub;
use App\Models\Officer;
use App\Models\OfficerSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializes an officer login session for manager read-only listing.
 *
 * Officer and hub summaries are included only when their relations have
 * already been eager loaded to avoid lazy queries.
 *
 * @mixin OfficerSession
 */
class OfficerSessionResource extends JsonResource
{
    /**
     * @var string|null
     */
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var OfficerSession $session */
        $session = $this->resource;

        return [
            'id' => $session->id,
            'officer_id' => $session->officer_id,
            'officer' => $this->compactOfficer($session),
            'personal_access_token_id' => $session->personal_access_token_id,
            'hub_id' => $session->hub_id,
            'hub' => $this->compactHub($session),
            'shift_start' => $session->shift_start?->toIso8601String(),
            'shift_end' => $session->shift_end?->toIso8601String(),
            'start_lat' => $session->start_lat,
            'start_lng' => $session->start_lng,
            'distance_meters_at_login' => $session->distance_meters_at_login,
            'is_hub_active' => (bool) $session->is_hub_active,
            'hub_active_until' => $session->hub_active_until?->toIso8601String(),
            'last_lat' => $session->last_lat,
            'last_lng' => $session->last_lng,
            'last_seen_at' => $session->last_seen_at?->toIso8601String(),
            'is_active' => (bool) $session->is_active,
            'created_at' => $session->created_at?->toIso8601String(),
            'updated_at' => $session->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function compactOfficer(OfficerSession $session): ?array
    {
        if (! $session->relationLoaded('officer') || ! $session->officer instanceof Officer) {
            return null;
        }

        $officer = $session->officer;

        return [
            'id' => $officer->id,
            'username' => $officer->username,
            'badge_number' => $officer->badge_number,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function compactHub(OfficerSession $session): ?array
    {
        if (! $session->relationLoaded('hub') || ! $session->hub instanceof Hub) {
            return null;
        }

        return (new HubResource($session->hub))->resolve();
    }
}
