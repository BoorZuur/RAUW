<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommunityPostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'visibility' => $this->visibility,
            'district' => $this->whenLoaded('district', fn () => [
                'id' => $this->district->id,
                'name' => $this->district->name,
                'postal_prefix' => $this->district->postal_prefix,
            ]),
            'officer' => $this->whenLoaded('officer', fn () => [
                'id' => $this->officer->id,
                'username' => $this->officer->username,
                'badge_number' => $this->officer->badge_number,
            ]),
            'attachments' => CommunityPostAttachmentResource::collection($this->whenLoaded('attachments')),
            'is_saved' => $this->when(isset($this->is_saved), fn () => (bool) $this->is_saved),
            'saved_count' => $this->when(isset($this->saved_count), fn () => (int) $this->saved_count),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
