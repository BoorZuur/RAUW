<?php

namespace App\Http\Requests\DistrictAssignments;

use App\Models\Manager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOfficerDistrictsRequest extends FormRequest
{
    /**
     * Only an authenticated, active manager may update an officer's district
     * assignments.
     *
     * Users, officers, and inactive managers are all rejected with a 403
     * response. Any active manager (not just a main manager) may manage officer
     * district assignments.
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
        return [
            'district_ids' => ['present', 'array'],
            'district_ids.*' => ['integer', 'distinct', Rule::exists('districts', 'id')->where('is_active', true)],
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
