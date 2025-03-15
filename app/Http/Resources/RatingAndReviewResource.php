<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RatingAndReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->review_id,
            'user_id' => $this->user_id,
            'product_id' => $this->product_id,
            'rating' => $this->rating,
            'original_language' => $this->original_language,
            'review_en' => $this->review_en,
            'review_ar' => $this->review_ar,
            'country' => $this->country,
            'created_at' => $this->created_at,
            'media' => $this->media,
            'publication_status' => $this->publication_status,
        ];
    }
}
