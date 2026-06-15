<?php

namespace App\Http\Requests\CommunityPosts;

use App\Enums\Visibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommunityPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() instanceof \App\Models\Officer && $this->user()->is_active;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'district_id' => [
                'required',
                'integer',
                Rule::exists('districts', 'id')->where('is_active', true),
            ],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:5000'],
            'visibility' => ['required', Rule::enum(Visibility::class)],
        ];
    }
}
