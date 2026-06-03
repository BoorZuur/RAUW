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
     * `distinct` prevents duplicate pivot assignments.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'district_ids' => ['present', 'array'],
            'district_ids.*' => ['integer', 'distinct', Rule::exists('districts', 'id')->where('is_active', true)],
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
