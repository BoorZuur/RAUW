<?php

namespace App\Http\Requests\Issues;

use App\Models\Officer;
use App\Support\OfficerIssueResolutionAttachments;
use App\Support\UploadedFileValidator;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreOfficerIssueResolutionRequest extends FormRequest
{
    /**
     * The maximum accepted size per uploaded file, expressed in kilobytes
     * (5 MB) to match Laravel's `max` file-size rule unit.
     */
    public const MAX_FILE_SIZE_KB = 5120;

    /**
     * Only an authenticated, active officer may create an officer resolution.
     *
     * District access, assignee checks, and duplicate-resolution guards run
     * on the locked issue row in the controller.
     */
    public function authorize(): bool
    {
        $actor = $this->user();

        return $actor instanceof Officer
            && (bool) $actor->is_active === true;
    }

    /**
     * `files` is capped at OfficerIssueResolutionAttachments::MAX_COUNT per request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:10000'],
            'files' => ['sometimes', 'array', 'max:'.OfficerIssueResolutionAttachments::MAX_COUNT],
            'files.*' => [
                'required',
                'file',
                'max:'.self::MAX_FILE_SIZE_KB,
                'mimes:jpg,jpeg,png,gif,webp',
            ],
        ];
    }

    /**
     * Reject uploads whose content does not match an allowed image type.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            UploadedFileValidator::validateUploadedFiles(
                $validator,
                $this->file('files'),
                UploadedFileValidator::assertAllowedImage(...),
            );
        });
    }
}
