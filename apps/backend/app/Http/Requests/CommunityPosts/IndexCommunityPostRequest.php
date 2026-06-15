<?php

namespace App\Http\Requests\CommunityPosts;

use Illuminate\Foundation\Http\FormRequest;

class IndexCommunityPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->is_active;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'district_id' => ['sometimes', 'integer', 'exists:districts,id'],
            'saved_only' => ['sometimes', 'boolean'],
        ];
    }
}
