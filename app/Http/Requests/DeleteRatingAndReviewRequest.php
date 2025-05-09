<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class DeleteRatingAndReviewRequest
 * 
 * Form request for validating requests to delete a review.
 * The review ID is expected as a route parameter.
 */
class DeleteRatingAndReviewRequest extends FormRequest
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
     * Get the validation rules that apply to the request for deleting a review.
     *
     * No specific body or query parameter rules are needed here, as the primary identifier (review ID)
     * comes from the route parameter. Route parameter validation (e.g., if it's a valid UUID format)
     * would typically be handled by route definition or controller method type hinting if strict.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     *         An empty array as no specific body/query rules are applied here.
     */
    public function rules(): array
    {
        // No rules for request body/query parameters are needed for a simple delete by ID.
        // The ID itself is a route parameter.
        return [];
    }

    /**
     * Prepare the data for validation.
     * 
     * This method can be used to modify request data before validation occurs.
     * In this case, it's primarily to ensure the route parameter 'id' is part of the data set 
     * if you were to apply rules to it directly in this FormRequest (though often not needed for route params).
     *
     * @return void
     */
    protected function prepareForValidation(): void // Added return type hint
    {
        // The route parameter 'id' for the review is implicitly part of the request context.
        // No specific data preparation is typically needed here if validation focuses on body/query params.
        // The validationData() method below explicitly adds it if needed for rule processing.
    }

    /**
     * Get the data to be validated.
     * 
     * This method merges all request data (body, query) with route parameters.
     * It ensures that route parameters like 'id' can be validated if rules were defined for them.
     *
     * @return array<string, mixed> The complete data set for validation.
     */
    public function validationData(): array // Added return type hint
    {
        // Merge all request inputs with route parameters to make route parameters available for validation rules.
        return array_merge($this->all(), [
            'id' => $this->route('id'), // Makes the 'id' route parameter available for validation rules if any were defined.
        ]);
    }
}
