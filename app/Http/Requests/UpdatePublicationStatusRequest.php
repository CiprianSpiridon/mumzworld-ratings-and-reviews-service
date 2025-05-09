<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates publication status updates for reviews
 */
class UpdatePublicationStatusRequest extends FormRequest
{
    /**
     * Determine request authorization
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true; // No specific authentication/authorization check required for this action currently.
    }

    /**
     * Get validation rules
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'publication_status' => 'required|string|in:pending,published,rejected', // Must be one of the defined statuses
        ];
    }

    /**
     * Prepare data before validation
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Route parameter 'id' is validated by Laravel's routing system
        // (e.g., type hinting in controller method or explicit route constraints)
        // and is required by definition in the route itself.
        // No other data preparation needed for this specific request before validation of 'publication_status'.
    }
}
