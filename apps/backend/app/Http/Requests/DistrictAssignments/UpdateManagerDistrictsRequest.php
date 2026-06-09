<?php

namespace App\Http\Requests\DistrictAssignments;

use App\Models\Manager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateManagerDistrictsRequest extends FormRequest
{
    /**
     * Only an authenticated, active main manager may update a manager's district
     * assignments.
     *
     * A main manager may update any ordinary manager. Users, officers,
     * non-main managers, inactive managers, and inactive main managers receive
     * a 403 response.
     *
     * `district_ids` must be present; an empty array clears all district
     * assignments. Every non-empty entry must reference an existing active
     * district row.
     */
    public function authorize(): bool
    {
        $actor = $this->user();

        return $actor instanceof Manager
            && (bool) $actor->is_active === true
            && (bool) $actor->is_main_manager === true;
    }

    /**
     * Validation rules for main-manager-driven manager district assignment updates.
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
        $manager = $this->route('manager');
        $hubId = $manager instanceof Manager ? $manager->hub_id : null;

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
     * The validated, de-duplicated district IDs to sync onto the manager.
     *
     * @return array<int, int>
     */
    public function districtIds(): array
    {
        return array_values(array_unique(array_map('intval', $this->input('district_ids', []))));
    }
}
