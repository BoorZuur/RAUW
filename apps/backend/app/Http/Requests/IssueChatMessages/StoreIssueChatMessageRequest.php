<?php

namespace App\Http\Requests\IssueChatMessages;

use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use App\Support\Issues\IssueChatMessageAttachments;
use App\Support\UploadedFileValidator;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreIssueChatMessageRequest extends FormRequest
{
    /**
     * Active users and officers may send messages. Managers are excluded.
     */
    public function authorize(): bool
    {
        $actor = $this->user();

        if ($actor instanceof Manager) {
            return false;
        }

        if ($actor instanceof Officer && (bool) $actor->is_active === true) {
            return true;
        }

        return $actor instanceof User && (bool) $actor->is_active === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'content' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'files' => ['sometimes', 'array', 'max:'.IssueChatMessageAttachments::MAX_COUNT],
            'files.*' => [
                'required',
                'file',
                'max:'.IssueChatMessageAttachments::MAX_FILE_SIZE_KB,
                'mimes:jpg,jpeg,png,gif,webp',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            UploadedFileValidator::validateUploadedFiles(
                $validator,
                $this->file('files'),
                UploadedFileValidator::assertAllowedImage(...),
            );

            $content = $this->input('content');
            $hasContent = is_string($content) && trim($content) !== '';
            $files = $this->file('files', []);

            if (! $hasContent && $files === []) {
                $validator->errors()->add(
                    'content',
                    'A message requires text content or at least one attachment.',
                );
                $validator->errors()->add(
                    'files',
                    'A message requires text content or at least one attachment.',
                );
            }
        });
    }
}
