<?php

namespace App\Http\Requests\IssueChatMessages;

use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class DownloadIssueMessageAttachmentRequest extends FormRequest
{
    /**
     * Active users and officers may download chat attachments. Managers are excluded.
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
        return [];
    }
}
