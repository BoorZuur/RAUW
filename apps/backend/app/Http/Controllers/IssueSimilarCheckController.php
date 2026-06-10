<?php

namespace App\Http\Controllers;

use App\Actions\Issues\FindSimilarIssues;
use App\Http\Requests\Issues\SimilarCheckIssueRequest;
use App\Http\Resources\SimilarIssueMatchResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class IssueSimilarCheckController extends Controller
{
    /**
     * Score open canonical issues in the request district and return the top
     * matches split into the actor's own reports and linkable candidates.
     */
    public function store(
        SimilarCheckIssueRequest $request,
        FindSimilarIssues $findSimilarIssues,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        $result = $findSimilarIssues->find($actor, $request->validated());

        return response()->json([
            'own_matches' => $this->serializeMatches($result['own_matches'], $request),
            'matches' => $this->serializeMatches($result['matches'], $request),
        ]);
    }

    /**
     * @param  list<array{issue: \App\Models\Issue, score: int, confidence: string, linkable: bool}>  $rows
     * @return list<array<string, mixed>>
     */
    private function serializeMatches(array $rows, SimilarCheckIssueRequest $request): array
    {
        return collect($rows)
            ->map(fn (array $row) => (new SimilarIssueMatchResource($row))->toArray($request))
            ->values()
            ->all();
    }
}
