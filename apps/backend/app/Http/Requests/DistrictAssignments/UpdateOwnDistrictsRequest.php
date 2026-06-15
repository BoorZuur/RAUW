<?php

namespace App\Http\Requests\DistrictAssignments;

use App\Models\Manager;
use App\Models\Officer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOwnDistrictsRequest extends FormRequest
{
    /**
     * Only an authenticated, active manager or officer may update their own
     * district assignments.
     *
     * Managers and officers are the only actors that carry district
     * assignments, so users (who have no district relationship) and any
     * unsupported actor are rejected with a 403 response. Inactive managers and
     * officers cannot authenticate, but the active flag is asserted here as a
     * defence-in-depth guard.
     *
     * When the actor has an assigned hub (`hub_id` is set), self-service
     * district assignment is limited to active districts within that hub;
     * cross-hub district IDs fail validation with 422 on `district_ids.*`.
     * Main managers are not exempt — only their issue visibility scope is
     * city-wide. Actors without an assigned hub may assign any active district.
     */
    public function authorize(): bool
    {
        $actor = $this->user();

        return ($actor instanceof Manager || $actor instanceof Officer)
            && (bool) $actor->is_active === true;
    }

    /**
     * Validation rules for self-service district assignment updates.
     *
     * `district_ids` must be present so the request unambiguously declares the
     * full desired assignment set; an empty array is allowed to clear all
     * districts. Every entry must reference an existing active district, and
     * `distinct` prevents duplicate pivot assignments. When the actor has an
     * assigned hub, districts must belong to that hub.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $actor = $this->user();
        $hubId = ($actor instanceof Manager || $actor instanceof Officer) ? $actor->hub_id : null;

        $districtExists = Rule::exists('districts', 'id')->where('is_active', true);

        if ($hubId !== null) {
            $districtExists = $districtExists->where('hub_id', $hubId);
        }

        return [
            'district_ids' => ['present', 'array'],
            'district_ids.*' => ['integer', 'distinct', $districtExists],
        ];
    }

    /**
     * The validated, de-duplicated district IDs to sync onto the actor.
     *
     * @return array<int, int>
     */
    public function districtIds(): array
    {
        return array_values(array_unique(array_map('intval', $this->input('district_ids', []))));
    }
}
