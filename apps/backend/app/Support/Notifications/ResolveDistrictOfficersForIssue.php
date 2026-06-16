<?php

namespace App\Support\Notifications;

use App\Models\Issue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ResolveDistrictOfficersForIssue
{
    /**
     * Officer ids in the issue district, filtered by department intersection when possible.
     *
     * When no officer matches both district and issue departments, all district officers
     * are returned as a fallback.
     *
     * @return Collection<int, int>
     */
    public function officerIds(Issue $canonical): Collection
    {
        if ($canonical->district_id === null) {
            return collect();
        }

        $canonical->loadMissing('departments');
        $departmentIds = $canonical->departments->pluck('id')->all();

        $districtOfficerIds = DB::table('district_officer')
            ->where('district_id', $canonical->district_id)
            ->pluck('officer_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        if ($districtOfficerIds->isEmpty()) {
            return collect();
        }

        if ($departmentIds === []) {
            return $districtOfficerIds;
        }

        $departmentOfficerIds = DB::table('department_officer')
            ->whereIn('department_id', $departmentIds)
            ->pluck('officer_id')
            ->map(static fn (mixed $id): int => (int) $id);

        $intersected = $districtOfficerIds
            ->intersect($departmentOfficerIds)
            ->unique()
            ->values();

        if ($intersected->isEmpty()) {
            return $districtOfficerIds;
        }

        return $intersected;
    }
}
