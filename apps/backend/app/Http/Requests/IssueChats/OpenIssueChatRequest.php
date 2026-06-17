<?php

namespace App\Http\Requests\IssueChats;

use App\Models\Officer;
use Illuminate\Foundation\Http\FormRequest;

class OpenIssueChatRequest extends FormRequest
{
    /**
     * Only an authenticated, active officer may open a chat.
     */
    public function authorize(): bool
    {
        $actor = $this->user();

        return $actor instanceof Officer
            && (bool) $actor->is_active === true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required_without:participant_id', 'integer', 'exists:users,id'],
            'participant_id' => ['required_without:user_id', 'integer', 'exists:issue_participants,id'],
        ];
    }
}
