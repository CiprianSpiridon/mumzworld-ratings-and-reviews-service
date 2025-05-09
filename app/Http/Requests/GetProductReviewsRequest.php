<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class GetProductReviewsRequest
 * 
 * Form request for validating requests to retrieve reviews for a specific product.
 * Handles optional query parameters for filtering.
 */
class GetProductReviewsRequest extends FormRequest
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
        return true; // No specific authentication/authorization check required for this action currently.
    }

    /**
     * Get the validation rules that apply to the request for fetching product reviews.
     *
     * Defines optional rules for filtering by country, language, user ID, and publication status.
     * The product ID itself is a route parameter, handled by controller/routing.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     *         An array of validation rules.
     */
    public function rules(): array
    {
        return [
            // Optional filter by 2-letter country code
            'country' => 'sometimes|string|size:2',
            // Optional filter by original language (en or ar)
            'language' => 'sometimes|string|in:en,ar',
            // Optional filter by user identifier
            'user_id' => 'sometimes|string|max:255',
            // Optional filter by publication status (pending, published, rejected, or 'all')
            'publication_status' => 'sometimes|string|in:pending,published,rejected,all' 
        ];
    }

    /**
     * Prepare the data for validation.
     * 
     * This method can be used to modify request data before validation occurs.
     * In this case, it's empty as the primary input (product 'id' from route) is handled by routing.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // The route parameter 'id' (product ID) is validated by Laravel's routing system
        // (e.g., type hinting in controller method or explicit route constraints)
        // and is required by definition in the route itself.
        // No data preparation needed here for optional query parameters.
    }
}
