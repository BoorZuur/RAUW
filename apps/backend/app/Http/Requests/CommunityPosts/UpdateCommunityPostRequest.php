<?php

namespace App\Http\Requests\CommunityPosts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCommunityPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $post = $this->route('community_post');

        if (! $post instanceof \App\Models\CommunityPost) {
            return false;
        }

        // Only the officer who created the post can update it
        return $this->user() instanceof \App\Models\Officer
            && $this->user()->is_active
            && $this->user()->id === $post->officer_id;
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
                'sometimes',
                'required',
                'integer',
                Rule::exists('districts', 'id')->where('is_active', true),
            ],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'content' => ['sometimes', 'required', 'string', 'max:5000'],
        ];
    }
}
