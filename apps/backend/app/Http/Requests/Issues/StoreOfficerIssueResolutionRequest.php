<?php

namespace App\Http\Requests\Issues;

use App\Models\Issue;
use App\Models\Officer;
use Illuminate\Foundation\Http\FormRequest;

class StoreOfficerIssueResolutionRequest extends FormRequest
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
     * Only the authenticated, active officer currently assigned to the issue
     * may create its officer resolution report.
     */
    public function authorize(): bool
    {
        $actor = $this->user();

        if (! $actor instanceof Officer || (bool) $actor->is_active !== true) {
            return false;
        }

        $issue = $this->route('issue');

        return $issue instanceof Issue
            && $issue->assigned_officer_id === $actor->getKey();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:10000'],
            'files' => ['sometimes', 'array', 'max:'.self::MAX_ATTACHMENTS],
            'files.*' => [
                'required',
                'file',
                'max:'.self::MAX_FILE_SIZE_KB,
                'mimes:jpg,jpeg,png,gif,webp',
            ],
        ];
    }
}
