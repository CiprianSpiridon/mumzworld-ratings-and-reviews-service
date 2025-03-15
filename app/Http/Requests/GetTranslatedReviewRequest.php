<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetTranslatedReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'language' => 'required|string|in:en,ar',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'language.required' => 'The language parameter is required.',
            'language.in' => 'The language must be either "en" or "ar".',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    public function prepareForValidation(): void
    {
        // The route parameter 'id' is already validated by Laravel's routing system
        // and is required by definition in the route
    }
}
