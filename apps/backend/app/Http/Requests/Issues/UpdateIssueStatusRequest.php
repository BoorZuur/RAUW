<?php

namespace App\Http\Requests\Issues;

use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\Officer;
use App\Support\IssueStatusTransition;
use App\Support\OfficerIssueDistrictAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Symfony\Component\HttpFoundation\Response;

class UpdateIssueStatusRequest extends FormRequest
{
    /**
     * Only an authenticated, active officer may update issue status.
     *
     * Users, managers, inactive officers, and unauthenticated requests are
     * rejected with a 403 response. District access, assignee checks, and
     * transition validation are enforced after the base field rules pass.
     */
    public function authorize(): bool
    {
        $actor = $this->user();

        return $actor instanceof Officer
            && (bool) $actor->is_active === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(IssueStatus::class)],
            'note' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Enforce district access, assignee ownership, and directed status workflow.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var Officer $officer */
            $officer = $this->user();

            /** @var Issue $issue */
            $issue = $this->route('issue');

            OfficerIssueDistrictAccess::assertOfficerInIssueDistrict($officer, $issue);

            if ($issue->assigned_officer_id !== $officer->getKey()) {
                throw new HttpResponseException(
                    response()->json([
                        'message' => 'Only the assigned officer may update this issue status.',
                        'code' => 'not_assigned_officer',
                    ], Response::HTTP_FORBIDDEN)
                );
            }

            /** @var IssueStatus $newStatus */
            $newStatus = $this->enum('status', IssueStatus::class);
            $oldStatus = $issue->status;

            if ($oldStatus === $newStatus) {
                $validator->errors()->add(
                    'status',
                    sprintf(
                        'Issue is already in status %s.',
                        $newStatus->value,
                    ),
                );

                return;
            }

            if (! IssueStatusTransition::canTransition($oldStatus, $newStatus)) {
                $validator->errors()->add(
                    'status',
                    sprintf(
                        'Invalid status transition from %s to %s.',
                        $oldStatus->value,
                        $newStatus->value,
                    ),
                );
            }
        });
    }
}
