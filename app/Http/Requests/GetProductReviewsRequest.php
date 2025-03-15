<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetProductReviewsRequest extends FormRequest
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
            'country' => 'sometimes|string|size:2',
            'language' => 'sometimes|string|in:en,ar',
            'user_id' => 'sometimes|string|max:255',
            'publication_status' => 'sometimes|string|in:pending,published,rejected,all',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // The route parameter 'id' is already validated by Laravel's routing system
        // and is required by definition in the route
    }
}
