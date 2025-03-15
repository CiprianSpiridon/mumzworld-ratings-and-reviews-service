<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRatingAndReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // No authentication required as per requirements
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|string|max:255',
            'product_id' => 'required|string|max:255',
            'rating' => 'required|integer|min:1|max:5',
            'original_language' => 'required|string|in:en,ar',
            'review_en' => 'required_if:original_language,en|nullable|string|max:1000',
            'review_ar' => 'required_if:original_language,ar|nullable|string|max:1000',
            'country' => 'required|string|size:2',
            'media_files' => 'nullable|array',
            'media_files.*' => 'file|mimes:jpeg,png,jpg,gif,mp4,mov,avi|max:10240', // 10MB max
        ];
    }
}
