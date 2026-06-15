<?php

namespace App\Support\Issues;

use App\Models\Issue;
use App\Support\Geo\Haversine;
use Carbon\CarbonInterface;

class SimilarIssueScorer
{
    private const CATEGORY_MATCH_POINTS = 40;

    private const RECENCY_MAX_POINTS = 30;

    private const POSTAL_PREFIX_POINTS = 20;

    private const DISTANCE_UNDER_100M_POINTS = 30;

    private const DISTANCE_UNDER_500M_POINTS = 20;

    private const DISTANCE_UNDER_2KM_POINTS = 10;

    private const SCORE_CAP = 100;

    private const HIGH_BAND_MIN = 70;

    private const MEDIUM_BAND_MIN = 40;

    /**
     * @return array{score: int, confidence: 'high'|'medium'|'low'}
     */
    public function score(
        int $requestCategoryId,
        ?string $requestPostalCode,
        ?float $requestLatitude,
        ?float $requestLongitude,
        Issue $candidate,
        ?CarbonInterface $now = null,
    ): array {
        $now ??= now();

        $score = 0;

        if ($candidate->category_id === $requestCategoryId) {
            $score += self::CATEGORY_MATCH_POINTS;
        }

        $daysSinceCreated = (int) $candidate->created_at?->diffInDays($now) ?? 0;
        $score += max(0, self::RECENCY_MAX_POINTS - $daysSinceCreated);

        $requestPrefix = $this->postalPrefix($requestPostalCode);
        $candidatePrefix = $this->postalPrefix($candidate->postal_code);

        if ($requestPrefix !== null
            && $candidatePrefix !== null
            && $requestPrefix === $candidatePrefix
        ) {
            $score += self::POSTAL_PREFIX_POINTS;
        }

        if ($requestLatitude !== null
            && $requestLongitude !== null
            && $candidate->latitude !== null
            && $candidate->longitude !== null
        ) {
            $distanceMeters = Haversine::distanceMeters(
                $requestLatitude,
                $requestLongitude,
                (float) $candidate->latitude,
                (float) $candidate->longitude,
            );

            if ($distanceMeters < 100) {
                $score += self::DISTANCE_UNDER_100M_POINTS;
            } elseif ($distanceMeters < 500) {
                $score += self::DISTANCE_UNDER_500M_POINTS;
            } elseif ($distanceMeters < 2_000) {
                $score += self::DISTANCE_UNDER_2KM_POINTS;
            }
        }

        $score = min(self::SCORE_CAP, $score);

        return [
            'score' => $score,
            'confidence' => $this->confidenceBand($score),
        ];
    }

    /**
     * @return 'high'|'medium'|'low'
     */
    public function confidenceBand(int $score): string
    {
        if ($score >= self::HIGH_BAND_MIN) {
            return 'high';
        }

        if ($score >= self::MEDIUM_BAND_MIN) {
            return 'medium';
        }

        return 'low';
    }

    private function postalPrefix(?string $postalCode): ?string
    {
        if ($postalCode === null || $postalCode === '') {
            return null;
        }

        $normalized = strtoupper(preg_replace('/\s+/', '', $postalCode) ?? '');

        if (strlen($normalized) < 4) {
            return null;
        }

        return substr($normalized, 0, 4);
    }
}
