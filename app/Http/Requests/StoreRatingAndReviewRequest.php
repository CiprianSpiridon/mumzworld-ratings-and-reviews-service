<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class StoreRatingAndReviewRequest
 * 
 * Form request for validating the creation of a new rating and review.
 * Defines authorization logic and validation rules for the incoming request data.
 */
class StoreRatingAndReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * In this case, authorization is open (returns true) as per current requirements.
     * This could be changed to implement specific authorization logic if needed.
     *
     * @return bool True if the request is authorized, false otherwise.
     */
    public function authorize(): bool
    {
        return true; // No specific authentication/authorization check required for this action currently.
    }

    /**
     * Get the validation rules that apply to the request for creating a review.
     *
     * Defines rules for user ID, product ID, rating, language, review text, country, and media files.
     * Publication status is not accepted here; it defaults to 'pending' in the model.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     *         An array of validation rules.
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|string|max:255', // User identifier
            'product_id' => 'required|string|max:255', // Product identifier
            'rating' => 'required|integer|min:1|max:5', // Rating score (1-5)
            'original_language' => 'required|string|in:en,ar', // Original language of the review (en or ar)
            'review_en' => 'required_if:original_language,en|nullable|string|max:1000', // English review text (required if original_language is 'en')
            'review_ar' => 'required_if:original_language,ar|nullable|string|max:1000', // Arabic review text (required if original_language is 'ar')
            'country' => 'required|string|size:2', // 2-letter country code
            'media_files' => 'nullable|array', // Optional array of media files
            'media_files.*' => 'file|mimes:jpeg,png,jpg,gif,mp4,mov,avi|max:10240', // Validation for each media file (type, size)
        ];
    }
}
