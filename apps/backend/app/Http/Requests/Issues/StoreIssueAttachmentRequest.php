<?php

namespace App\Http\Requests\Issues;

use App\Models\Issue;
use App\Models\User;
use App\Support\UploadedFileValidator;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreIssueAttachmentRequest extends FormRequest
{
    /**
     * The maximum number of attachments an issue may hold at once.
     */
    public const MAX_ATTACHMENTS = 5;

    /**
     * The maximum accepted size per uploaded file, expressed in kilobytes
     * (5 MB) to match Laravel's `max` file-size rule unit.
     */
    public const MAX_FILE_SIZE_KB = 5120;

    /**
     * Only the authenticated, active regular user who owns the target issue may
     * upload attachments to it.
     *
     * Mirrors UpdateIssueRequest/DeleteIssueRequest: officers, managers,
     * inactive users, unauthenticated requests, and any non-owner user are all
     * rejected with a 403 response. Ownership is asserted against
     * `issues.user_id`, which is retained even for anonymous reports so the
     * author can still manage their own report's attachments.
     */
    public function authorize(): bool
    {
        $actor = $this->user();

        if (! $actor instanceof User || (bool) $actor->is_active !== true) {
            return false;
        }

        $issue = $this->route('issue');

        return $issue instanceof Issue
            && $issue->user_id === $actor->getKey();
    }

    /**
     * Validation rules for attachment uploads.
     *
     * Uploads arrive as a `files` array of up to MAX_ATTACHMENTS items. Each
     * entry must be an uploaded file no larger than MAX_FILE_SIZE_KB and limited
     * to the accepted image and document MIME types. The per-request `max` on
     * the array bounds a single upload; the cumulative cap against already
     * stored attachments is enforced in withValidator().
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1', 'max:'.self::MAX_ATTACHMENTS],
            'files.*' => [
                'required',
                'file',
                'max:'.self::MAX_FILE_SIZE_KB,
                'mimes:jpg,jpeg,png,gif,webp,pdf',
            ],
        ];
    }

    /**
     * Enforce the total attachment cap across existing and incoming files.
     *
     * The per-request array `max` rule bounds a single upload, but an issue must
     * never exceed MAX_ATTACHMENTS stored attachments overall. This check
     * combines the issue's already-persisted attachment count with the number of
     * files in this request and fails validation when the combined total would
     * breach the cap, so repeated partial uploads cannot accumulate past it.
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
                UploadedFileValidator::assertAllowedIssueAttachment(...),
            );

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $issue = $this->route('issue');

            if (! $issue instanceof Issue) {
                return;
            }

            $incoming = $this->file('files');

            if (! is_array($incoming)) {
                return;
            }

            $existing = $issue->attachments()->count();

            if ($existing + count($incoming) > self::MAX_ATTACHMENTS) {
                $remaining = max(0, self::MAX_ATTACHMENTS - $existing);

                $validator->errors()->add(
                    'files',
                    "This issue can have at most ".self::MAX_ATTACHMENTS." attachments. ".
                    "It already has {$existing}, so you may upload {$remaining} more."
                );
            }
        });
    }
}
