<?php

namespace App\Http\Requests\CommunityPosts;

use Illuminate\Foundation\Http\FormRequest;

class DownloadCommunityPostAttachmentRequest extends FormRequest
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

        // Anyone who can view the post can download its attachments
        /** @var \App\Support\CommunityPostVisibilityQuery $visibilityQuery */
        $visibilityQuery = app(\App\Support\CommunityPostVisibilityQuery::class);

        return $visibilityQuery->isVisible($this->user(), $post);
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
