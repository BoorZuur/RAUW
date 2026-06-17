<?php

namespace App\Http\Requests\DistrictAssignments;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserFeedDistrictsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() instanceof \App\Models\User && $this->user()->is_active;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'district_ids' => ['present', 'array'],
            'district_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('districts', 'id')->where('is_active', true),
            ],
        ];
    }
}
