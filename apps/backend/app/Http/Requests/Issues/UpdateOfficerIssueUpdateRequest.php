<?php

namespace App\Http\Requests\Issues;

use App\Models\Officer;
use App\Support\UploadedFileValidator;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOfficerIssueUpdateRequest extends FormRequest
{
    public const MAX_FILE_SIZE_KB = 5120;

    /**
     * Only an authenticated, active officer may update an update.
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
            'files' => ['sometimes', 'array', 'max:3'],
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
     * Sniff uploaded images for allowed MIME types.
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
        });
    }
}
