<?php

namespace App\Http\Controllers;

use App\Enums\IssueStatus;
use App\Http\Requests\Issues\DestroyIssueFeedbackRequest;
use App\Http\Requests\Issues\IndexIssueFeedbackRequest;
use App\Http\Requests\Issues\StoreIssueFeedbackRequest;
use App\Http\Requests\Issues\UpdateIssueFeedbackRequest;
use App\Http\Resources\IssueFeedbackResource;
use App\Models\Issue;
use App\Models\IssueFeedback;
use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use App\Support\IssueFeedbackIntegrity;
use App\Support\Issues\IssueAnonymousDisplayName;
use App\Support\Issues\IssueFeedbackOfficerAccess;
use App\Support\Issues\IssueFeedbackWindow;
use App\Support\Issues\IssueParticipantAccess;
use App\Support\IssueVisibilityQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class IssueFeedbackController extends Controller
{
    private const FEEDBACK_RELATIONS = ['reviewer', 'issue'];

    public function index(IndexIssueFeedbackRequest $request, Issue $issue): AnonymousResourceCollection
    {
        $actor = $request->user();

        if (! IssueVisibilityQuery::canViewIssue($issue, $actor)) {
            abort(404);
        }

        $query = $issue->feedback()->with(self::FEEDBACK_RELATIONS);

        if ($actor instanceof User) {
            $query->where('reviewer_user_id', $actor->id);
        } elseif ($actor instanceof Officer) {
            if (! IssueFeedbackOfficerAccess::hasInvolvement($actor, $issue)) {
                // Officer can only read feedback on issues they were assigned to or authored resolution for.
                abort(403, 'not_involved_officer'); // Or return empty collection? Plan says visibility matrix: officer sees feedback on involved issues.
                // It does not explicitly specify error, but 403 makes sense if attempting to read an issue's feedback they aren't involved in.
                // Wait, maybe we just return an empty collection? The plan says: "Officers see all feedback rows on issues where officer was ever assignee...".
                // Since `canViewIssue` allows them to view the issue, accessing its feedback should probably return empty or 403. Let's return 403 to match strict access.
                abort(403);
            }
        } elseif ($actor instanceof Manager) {
            // Manager sees all rows
        } else {
            abort(403);
        }

        $feedbacks = $query->get();
        IssueAnonymousDisplayName::preloadForFeedbacks($feedbacks);

        return IssueFeedbackResource::collection($feedbacks);
    }

    public function store(StoreIssueFeedbackRequest $request, Issue $issue): JsonResponse
    {
        $user = $request->user();

        if (! IssueVisibilityQuery::canViewIssue($issue, $user)) {
            abort(404);
        }

        IssueParticipantAccess::assertParticipant($user, $issue);

        if ($issue->status !== IssueStatus::Closed) {
            abort(403, 'feedback_not_allowed');
        }

        if (! IssueFeedbackWindow::isOpen($issue)) {
            abort(403, 'feedback_window_closed');
        }

        $validated = $request->validated();

        return IssueFeedbackIntegrity::wrap(function () use ($issue, $validated, $user) {
            $feedback = $issue->feedback()->create([
                'reviewer_user_id' => $user->id,
                'is_satisfied' => $validated['is_satisfied'],
                'comment' => $validated['comment'] ?? null,
                'submitted_at' => now(),
            ]);

            return (new IssueFeedbackResource($feedback->load(self::FEEDBACK_RELATIONS)))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
        });
    }

    public function update(UpdateIssueFeedbackRequest $request, Issue $issue, IssueFeedback $feedback): IssueFeedbackResource
    {
        if ($feedback->issue_id !== $issue->id) {
            abort(404);
        }

        if (! IssueVisibilityQuery::canViewIssue($issue, $request->user())) {
            abort(404);
        }

        if ($issue->status !== IssueStatus::Closed) {
            abort(403, 'feedback_not_allowed');
        }

        if (! IssueFeedbackWindow::isOpen($issue)) {
            abort(403, 'feedback_window_closed');
        }

        if (! IssueFeedbackWindow::isEditable($feedback)) {
            abort(403, 'feedback_edit_window_closed');
        }

        $validated = $request->validated();

        if (array_key_exists('is_satisfied', $validated)) {
            $feedback->is_satisfied = $validated['is_satisfied'];
        }
        if (array_key_exists('comment', $validated)) {
            $feedback->comment = $validated['comment'];
        }

        $feedback->updated_at = now();
        $feedback->save();

        return new IssueFeedbackResource($feedback->load(self::FEEDBACK_RELATIONS));
    }

    public function destroy(DestroyIssueFeedbackRequest $request, Issue $issue, IssueFeedback $feedback): Response
    {
        if ($feedback->issue_id !== $issue->id) {
            abort(404);
        }

        if (! IssueVisibilityQuery::canViewIssue($issue, $request->user())) {
            abort(404);
        }

        if ($issue->status !== IssueStatus::Closed) {
            abort(403, 'feedback_not_allowed');
        }

        if (! IssueFeedbackWindow::isOpen($issue)) {
            abort(403, 'feedback_window_closed');
        }

        if (! IssueFeedbackWindow::isEditable($feedback)) {
            abort(403, 'feedback_edit_window_closed');
        }

        $feedback->delete();

        return response()->noContent();
    }
}
