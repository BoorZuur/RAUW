<?php

namespace App\Http\Requests\Issues;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class DestroyIssueFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $feedback = $this->route('feedback');

        return $user instanceof User && $feedback->reviewer_user_id === $user->id;
    }

    public function rules(): array
    {
        return [];
    }
}
