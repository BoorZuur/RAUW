<?php

namespace App\Http\Requests\Officers;

use App\Models\Officer;
use Illuminate\Foundation\Http\FormRequest;

class IndexOfficerMeFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof Officer;
    }

    public function rules(): array
    {
        return [
            'issue_id' => ['sometimes', 'integer', 'exists:issues,id'],
            'submitted_from' => ['sometimes', 'date'],
            'submitted_to' => ['sometimes', 'date', 'after_or_equal:submitted_from'],
        ];
    }
}
