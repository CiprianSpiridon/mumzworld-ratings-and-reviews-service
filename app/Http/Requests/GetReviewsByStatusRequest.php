<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class GetReviewsByStatusRequest
 * 
 * Form request for validating requests to retrieve reviews, typically filtered by status
 * and other criteria, with support for pagination.
 */
class GetReviewsByStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * Currently allows all requests. Implement specific authorization if needed.
     *
     * @return bool True if authorized, false otherwise.
     */
    public function authorize(): bool
    {
        // No specific authentication/authorization check required for this action currently.
        return true;
    }

    /**
     * Get the validation rules that apply to the request for fetching reviews by status.
     *
     * Defines optional rules for filtering and pagination parameters.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     *         An array of validation rules.
     */
    public function rules(): array
    {
        return [
            // Optional filter by publication status
            'publication_status' => 'sometimes|string|in:pending,published,rejected',
            // Optional filter by 2-letter country code
            'country' => 'sometimes|string|size:2',
            // Optional filter by original language (en or ar)
            'language' => 'sometimes|string|in:en,ar',
            // Optional: Number of reviews per page for pagination
            'per_page' => 'sometimes|integer|min:1|max:100', 
            // Optional: Page number (primarily for traditional pagination, less used with next_token)
            'page' => 'sometimes|integer|min:1',
            // Optional: Flag to invalidate CloudFront cache for this endpoint call
            'invalidate_cache' => 'sometimes|boolean',
            // Optional: Token for DynamoDB key-based pagination (string format varies)
            'next_token' => 'sometimes|string',
        ];
    }
}
