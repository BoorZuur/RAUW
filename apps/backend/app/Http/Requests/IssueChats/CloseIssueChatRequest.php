<?php

namespace App\Http\Requests\IssueChats;

use App\Models\Officer;
use Illuminate\Foundation\Http\FormRequest;

class CloseIssueChatRequest extends FormRequest
{
    /**
     * Only an authenticated, active officer may close a chat.
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
        return [];
    }
}
