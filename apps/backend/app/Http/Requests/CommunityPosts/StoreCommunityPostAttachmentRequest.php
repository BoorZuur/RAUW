<?php

namespace App\Http\Requests\CommunityPosts;

use App\Models\CommunityPost;
use Illuminate\Foundation\Http\FormRequest;

class StoreCommunityPostAttachmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var CommunityPost|null $post */
        $post = $this->route('community_post');

        if (! $post instanceof CommunityPost) {
            return false;
        }

        // Only the officer who created the post can add attachments
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
            'attachments' => ['required', 'array', 'min:1', 'max:5'],
            'attachments.*' => ['file', 'max:5120'], // 5MB max
        ];
    }
}
