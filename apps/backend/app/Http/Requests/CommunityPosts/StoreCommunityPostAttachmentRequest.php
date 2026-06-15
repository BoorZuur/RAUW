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

        // Only the officer who created the post or an officer in the post's district can add attachments
        if (! $this->user() instanceof \App\Models\Officer || ! $this->user()->is_active) {
            return false;
        }
        
        /** @var \App\Models\Officer $officer */
        $officer = $this->user();
        
        if ($post->district_id === null) {
            return $officer->id === $post->officer_id;
        }

        return \App\Support\OfficerCommunityPostDistrictAccess::officerInPostDistrict($officer, $post);
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
