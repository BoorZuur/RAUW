<?php

namespace App\Http\Controllers;

use App\Http\Requests\Officers\IndexOfficerMeFeedbackRequest;
use App\Http\Resources\IssueFeedbackResource;
use App\Models\IssueFeedback;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OfficerMeFeedbackController extends Controller
{
    public function index(IndexOfficerMeFeedbackRequest $request): AnonymousResourceCollection
    {
        $officer = $request->user();
        $validated = $request->validated();

        $query = IssueFeedback::query()
            ->with(['issue', 'reviewer'])
            ->whereHas('issue', function ($issueQuery) use ($officer) {
                $issueQuery->where(function ($q) use ($officer) {
                    $q->whereHas('officerAssignmentHistories', function ($historyQ) use ($officer) {
                        $historyQ->where('officer_id', $officer->id);
                    })->orWhereHas('officerResolution', function ($resQ) use ($officer) {
                        $resQ->where('officer_id', $officer->id);
                    });
                });
            });

        if (isset($validated['issue_id'])) {
            $query->where('issue_id', $validated['issue_id']);
        }

        if (isset($validated['submitted_from'])) {
            $query->where('submitted_at', '>=', $validated['submitted_from']);
        }

        if (isset($validated['submitted_to'])) {
            $query->where('submitted_at', '<=', $validated['submitted_to']);
        }

        return IssueFeedbackResource::collection($query->paginate());
    }
}
