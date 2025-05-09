<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class GetTranslatedReviewRequest
 * 
 * Form request for validating requests to retrieve a translated review.
 * Ensures the target language is provided and supported.
 */
class GetTranslatedReviewRequest extends FormRequest
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
     * Get the validation rules that apply to the request for fetching a translated review.
     *
     * Requires the 'language' query parameter and validates it against supported languages.
     * The review ID is a route parameter, handled by controller/routing.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     *         An array of validation rules.
     */
    public function rules(): array
    {
        return [
            // Target language for translation (must be 'en' or 'ar')
            'language' => 'required|string|in:en,ar',
        ];
    }

    /**
     * Get custom messages for validator errors.
     * 
     * Provides user-friendly error messages for language validation failures.
     *
     * @return array<string, string> An array of custom error messages.
     */
    public function messages(): array
    {
        return [
            'language.required' => 'The language query parameter is required.',
            'language.in' => 'The language must be either "en" or "ar".',
        ];
    }

    /**
     * Prepare the data for validation.
     * 
     * This method can be used to modify request data before validation occurs.
     * In this case, it's empty as the primary input (review 'id' from route) is handled by routing.
     *
     * @return void
     */
    public function prepareForValidation(): void
    {
        // The route parameter 'id' (review ID) is validated by Laravel's routing system
        // (e.g., type hinting in controller method or explicit route constraints)
        // and is required by definition in the route itself.
        // No data preparation needed here for the 'language' query parameter.
    }
}
