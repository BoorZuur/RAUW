<?php

namespace App\Support;

use App\Models\Issue;
use App\Models\Manager;
use App\Models\Officer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class ActorDistrictAccess
{
    /**
     * @var array<string, array<int>>
     */
    private static array $assignedDistrictIdsCache = [];

    /**
     * District IDs assigned to the actor via district_officer or district_manager.
     *
     * @return array<int>
     */
    public static function assignedDistrictIds(Officer|Manager $actor): array
    {
        $cacheKey = $actor::class.':'.$actor->getKey();

        if (! array_key_exists($cacheKey, self::$assignedDistrictIdsCache)) {
            if ($actor->relationLoaded('districts')) {
                self::$assignedDistrictIdsCache[$cacheKey] = $actor->districts
                    ->pluck('id')
                    ->map(static fn ($id): int => (int) $id)
                    ->all();
            } else {
                self::$assignedDistrictIdsCache[$cacheKey] = array_map(
                    'intval',
                    $actor->districts()->pluck('districts.id')->all()
                );
            }
        }

        return self::$assignedDistrictIdsCache[$cacheKey];
    }

    public static function isMainManager(Manager $actor): bool
    {
        return $actor->is_main_manager === true;
    }

    /**
     * Whether the actor may access the issue's district.
     *
     * Main managers: always true. Officers and ordinary managers: issue district
     * must be in assigned districts; null district_id on the issue → false.
     */
    public static function actorInIssueDistrict(Officer|Manager $actor, Issue $issue): bool
    {
        if ($actor instanceof Manager && self::isMainManager($actor)) {
            return true;
        }

        if ($issue->district_id === null) {
            return false;
        }

        return in_array((int) $issue->district_id, self::assignedDistrictIds($actor), true);
    }

    /**
     * Restrict a query to issues in the actor's assigned districts.
     *
     * Main managers: unchanged (city-wide). Others: district_id IN assigned ids;
     * empty assignments → no rows.
     */
    public static function applyDistrictScope(Builder $query, Officer|Manager $actor): Builder
    {
        if ($actor instanceof Manager && self::isMainManager($actor)) {
            return $query;
        }

        $ids = self::assignedDistrictIds($actor);

        if ($ids === []) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereIn('district_id', $ids);
    }

    /**
     * Assert the actor is assigned to the issue's district or abort 403.
     *
     * Main managers always pass. Must be called outside row-lock transactions;
     * district membership is immutable for the duration of an issue write and
     * throws HttpResponseException, which must not run inside DB::transaction.
     */
    public static function assertActorInIssueDistrict(Officer|Manager $actor, Issue $issue): void
    {
        if ($actor instanceof Manager && self::isMainManager($actor)) {
            return;
        }

        if (! self::actorInIssueDistrict($actor, $issue)) {
            $message = $actor instanceof Officer
                ? 'Officer is not assigned to this issue district.'
                : 'Manager is not assigned to this issue district.';

            throw new HttpResponseException(
                response()->json([
                    'message' => $message,
                    'code' => 'actor_not_in_district',
                ], Response::HTTP_FORBIDDEN)
            );
        }
    }
}
