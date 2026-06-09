<?php

namespace App\Support;

use App\Models\Issue;
use App\Models\Officer;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class OfficerIssueDistrictAccess
{
    /**
     * Whether the officer is assigned to the issue's district via district_officer.
     */
    public static function officerInIssueDistrict(Officer $officer, Issue $issue): bool
    {
        if ($issue->district_id === null) {
            return false;
        }

        return $officer->districts()
            ->where('districts.id', $issue->district_id)
            ->exists();
    }

    /**
     * Assert the officer is assigned to the issue's district or abort 403.
     *
     * Must be called outside row-lock transactions; district membership is
     * immutable for the duration of an issue write and throws
     * HttpResponseException, which must not run inside DB::transaction.
     */
    public static function assertOfficerInIssueDistrict(Officer $officer, Issue $issue): void
    {
        if (! self::officerInIssueDistrict($officer, $issue)) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'Officer is not assigned to this issue district.',
                    'code' => 'officer_not_in_district',
                ], Response::HTTP_FORBIDDEN)
            );
        }
    }
}
