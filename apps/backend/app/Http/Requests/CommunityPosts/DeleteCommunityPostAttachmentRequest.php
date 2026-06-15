<?php

namespace App\Http\Requests\CommunityPosts;

use Illuminate\Foundation\Http\FormRequest;

class DeleteCommunityPostAttachmentRequest extends FormRequest
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

        // Managers can delete any attachment, officers only attachments on their own posts
        if ($this->user() instanceof \App\Models\Manager && $this->user()->is_active) {
            return true;
        }

        if ($this->user() instanceof \App\Models\Officer && $this->user()->is_active) {
            return $this->user()->id === $post->officer_id;
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
