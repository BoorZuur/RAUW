<?php

namespace App\Actions\Issues;

use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\User;
use App\Support\IssueVisibilityQuery;
use App\Support\Issues\SimilarIssueScorer;
use Illuminate\Support\Collection;

class FindSimilarIssues
{
    private const CANDIDATE_LIMIT = 50;

    private const RESULT_LIMIT = 5;

    public function __construct(
        private readonly SimilarIssueScorer $scorer,
    ) {}

    /**
     * @param  array{
     *     district_id: int,
     *     category_id: int,
     *     postal_code?: string|null,
     *     address?: string|null,
     *     latitude?: float|null,
     *     longitude?: float|null,
     * }  $input
     * @return array{
     *     own_matches: list<array{issue: Issue, score: int, confidence: string, linkable: bool}>,
     *     matches: list<array{issue: Issue, score: int, confidence: string, linkable: bool}>,
     * }
     */
    public function find(User $actor, array $input): array
    {
        $query = Issue::query()
            ->select([
                'id',
                'user_id',
                'title',
                'status',
                'category_id',
                'district_id',
                'duplicate_count',
                'participant_count',
                'created_at',
                'postal_code',
                'latitude',
                'longitude',
            ]);

        IssueVisibilityQuery::applyVisibilityScope($query, $actor);

        $candidates = $query
            ->where('district_id', $input['district_id'])
            ->whereIn('status', [IssueStatus::Open, IssueStatus::InProgress])
            ->whereNull('duplicate_of_id')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::CANDIDATE_LIMIT)
            ->get();

        $requestLatitude = $input['latitude'] ?? null;
        $requestLongitude = $input['longitude'] ?? null;
        $requestPostalCode = $input['postal_code'] ?? null;
        $requestCategoryId = $input['category_id'];

        $scored = $candidates
            ->map(function (Issue $issue) use (
                $requestCategoryId,
                $requestPostalCode,
                $requestLatitude,
                $requestLongitude,
            ): array {
                $result = $this->scorer->score(
                    $requestCategoryId,
                    $requestPostalCode,
                    $requestLatitude,
                    $requestLongitude,
                    $issue,
                );

                return [
                    'issue' => $issue,
                    'score' => $result['score'],
                    'confidence' => $result['confidence'],
                ];
            })
            ->sort(function (array $left, array $right): int {
                if ($left['score'] !== $right['score']) {
                    return $right['score'] <=> $left['score'];
                }

                /** @var Issue $leftIssue */
                $leftIssue = $left['issue'];
                /** @var Issue $rightIssue */
                $rightIssue = $right['issue'];

                $createdAtComparison = $rightIssue->created_at <=> $leftIssue->created_at;

                if ($createdAtComparison !== 0) {
                    return $createdAtComparison;
                }

                return $rightIssue->getKey() <=> $leftIssue->getKey();
            })
            ->values()
            ->take(self::RESULT_LIMIT);

        return $this->splitMatches($scored, $actor);
    }

    /**
     * @param  Collection<int, array{issue: Issue, score: int, confidence: string}>  $scored
     * @return array{
     *     own_matches: list<array{issue: Issue, score: int, confidence: string, linkable: bool}>,
     *     matches: list<array{issue: Issue, score: int, confidence: string, linkable: bool}>,
     * }
     */
    private function splitMatches(Collection $scored, User $actor): array
    {
        $ownMatches = [];
        $matches = [];

        foreach ($scored as $entry) {
            /** @var Issue $issue */
            $issue = $entry['issue'];
            $isOwn = $issue->user_id === $actor->getKey();

            $row = [
                'issue' => $issue,
                'score' => $entry['score'],
                'confidence' => $entry['confidence'],
                'linkable' => ! $isOwn,
            ];

            if ($isOwn) {
                $ownMatches[] = $row;
            } else {
                $matches[] = $row;
            }
        }

        return [
            'own_matches' => $ownMatches,
            'matches' => $matches,
        ];
    }

    public function __invoke(User $actor, array $input): array
    {
        return $this->find($actor, $input);
    }
}
