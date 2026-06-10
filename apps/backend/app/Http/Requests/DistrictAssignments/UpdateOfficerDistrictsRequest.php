<?php

namespace App\Http\Requests\DistrictAssignments;

use App\Models\Manager;
use App\Models\Officer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOfficerDistrictsRequest extends FormRequest
{
    /**
     * Only an authenticated, active manager may update an officer's district
     * assignments.
     *
     * Users, officers, and inactive managers are rejected with 403. Hub scoping
     * is enforced in the controller via ManagerOfficerHubAccess; hub mismatch
     * or null hub returns 404.
     */
    public function authorize(): bool
    {
        $actor = $this->user();

        return $actor instanceof Manager
            && (bool) $actor->is_active === true;
    }

    /**
     * Validation rules for manager-driven officer district assignment updates.
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
        $officer = $this->route('officer');
        $hubId = $officer instanceof Officer ? $officer->hub_id : null;

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
     * The validated, de-duplicated district IDs to sync onto the officer.
     *
     * @return array<int, int>
     */
    public function districtIds(): array
    {
        return array_values(array_unique(array_map('intval', $this->input('district_ids', []))));
    }
}
