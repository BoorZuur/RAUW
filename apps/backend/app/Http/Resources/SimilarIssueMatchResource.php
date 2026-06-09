<?php

namespace App\Http\Resources;

use App\Models\Issue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializes a scored similar-issue candidate for similar-check responses.
 *
 * @mixin array{issue: Issue, score: int, confidence: string, linkable: bool}
 */
class SimilarIssueMatchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Issue $issue */
        $issue = $this->resource['issue'];

        return [
            'id' => $issue->id,
            'title' => $issue->title,
            'status' => $issue->status->value,
            'category_id' => $issue->category_id,
            'district_id' => $issue->district_id,
            'duplicate_count' => $issue->duplicate_count,
            'participant_count' => $issue->participant_count,
            'created_at' => $issue->created_at?->toIso8601String(),
            'score' => $this->resource['score'],
            'confidence' => $this->resource['confidence'],
            'linkable' => (bool) $this->resource['linkable'],
        ];
    }
}
