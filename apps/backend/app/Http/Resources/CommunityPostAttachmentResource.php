<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommunityPostAttachmentResource extends JsonResource
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
            'file_url' => $this->file_url,
            'original_name' => $this->original_name,
            'file_type' => $this->file_type,
            'file_size' => $this->file_size,
            'uploaded_at' => $this->uploaded_at?->toIso8601String(),
        ];
    }
}
