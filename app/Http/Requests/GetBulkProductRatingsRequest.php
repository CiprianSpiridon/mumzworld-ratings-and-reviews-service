<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class GetBulkProductRatingsRequest
 * 
 * Form request for validating requests to retrieve rating summaries for multiple products.
 */
class GetBulkProductRatingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * @return bool
     */
    public function authorize(): bool
    {
        // Assuming this endpoint is customer-facing and doesn't require specific user authorization beyond API access.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Validates that 'product_ids' is provided, is an array, and each element is a string.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_ids'   => 'required|array|min:1', // Must be an array with at least one ID
            'product_ids.*' => 'required|string|max:255', // Each item in the array must be a string (product ID format)
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_ids.required' => 'The product_ids field is required.',
            'product_ids.array'    => 'The product_ids must be an array.',
            'product_ids.min'      => 'At least one product ID must be provided in the product_ids array.',
            'product_ids.*.required' => 'Each product ID in the array is required.',
            'product_ids.*.string'   => 'Each product ID must be a string.',
            'product_ids.*.max'      => 'Each product ID may not be greater than 255 characters.',
        ];
    }
} 