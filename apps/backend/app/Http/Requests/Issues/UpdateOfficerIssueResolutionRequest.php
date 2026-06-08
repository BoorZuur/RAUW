<?php

namespace App\Http\Requests\Issues;

use App\Models\Issue;
use App\Models\Officer;
use App\Models\OfficerIssueResolution;
use App\Support\UploadedFileValidator;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOfficerIssueResolutionRequest extends FormRequest
{
    /**
     * The maximum number of image attachments a resolution may hold.
     */
    public const MAX_ATTACHMENTS = 3;

    /**
     * The maximum accepted size per uploaded file, expressed in kilobytes
     * (5 MB) to match Laravel's `max` file-size rule unit.
     */
    public const MAX_FILE_SIZE_KB = 5120;

    /**
     * Only an authenticated, active officer may update an officer resolution.
     *
     * District access, assignee checks, and attachment-cap validation run
     * on the locked issue row in the controller.
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
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:10000'],
            'files' => ['sometimes', 'array'],
            'files.*' => [
                'required',
                'file',
                'max:'.self::MAX_FILE_SIZE_KB,
                'mimes:jpg,jpeg,png,gif,webp',
            ],
            'remove_attachment_ids' => ['sometimes', 'array'],
            'remove_attachment_ids.*' => ['integer', 'distinct'],
        ];
    }

    /**
     * Enforce attachment ownership and the cumulative attachment cap.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            UploadedFileValidator::validateUploadedFiles(
                $validator,
                $this->file('files'),
                UploadedFileValidator::assertAllowedImage(...),
            );

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $issue = $this->route('issue');

            if (! $issue instanceof Issue) {
                return;
            }

            $resolution = $issue->officerResolution;

            if (! $resolution instanceof OfficerIssueResolution) {
                return;
            }

            $removeIds = $this->input('remove_attachment_ids', []);

            if (! is_array($removeIds)) {
                return;
            }

            $incoming = $this->file('files');

            if (! is_array($incoming)) {
                $incoming = [];
            }

            if ($removeIds !== []) {
                $ownedCount = $resolution->attachments()
                    ->whereIn('id', $removeIds)
                    ->count();

                if ($ownedCount !== count($removeIds)) {
                    $validator->errors()->add(
                        'remove_attachment_ids',
                        'One or more attachment ids do not belong to this resolution.',
                    );

                    return;
                }
            }

            $existing = $resolution->attachments()->count();
            $remaining = $existing - count($removeIds);

            if ($remaining + count($incoming) > self::MAX_ATTACHMENTS) {
                $allowed = max(0, self::MAX_ATTACHMENTS - $remaining);

                $validator->errors()->add(
                    'files',
                    'This resolution can have at most '.self::MAX_ATTACHMENTS.' attachments. '.
                    "After removals, you may upload {$allowed} more.",
                );
            }
        });
    }
}
