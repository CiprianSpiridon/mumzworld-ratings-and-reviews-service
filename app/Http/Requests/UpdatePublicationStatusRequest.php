<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdatePublicationStatusRequest
 * 
 * Form request for validating the update of a review's publication status.
 */
class UpdatePublicationStatusRequest extends FormRequest
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
     * Get the validation rules that apply to the request for updating publication status.
     *
     * Ensures 'publication_status' is provided and is one of the allowed values.
     * The review ID is typically a route parameter and handled by controller/routing.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     *         An array of validation rules.
     */
    public function rules(): array
    {
        return [
            'publication_status' => 'required|string|in:pending,published,rejected', // Must be one of the defined statuses
        ];
    }

    /**
     * Prepare the data for validation.
     * 
     * This method can be used to modify request data before validation occurs.
     * In this case, it's empty as the primary input ('id' from route) is handled by routing.
     *
     * @return void
     */
    protected function prepareForValidation(): void // Added return type hint
    {
        // The route parameter 'id' for the review is validated by Laravel's routing system
        // (e.g., type hinting in controller method or explicit route constraints)
        // and is required by definition in the route itself.
        // No other data preparation needed for this specific request before validation of 'publication_status'.
    }
}
