<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetReviewsByStatusRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'publication_status' => 'sometimes|string|in:pending,published,rejected',
            'country' => 'sometimes|string|size:2',
            'language' => 'sometimes|string|in:en,ar',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
            'invalidate_cache' => 'sometimes|boolean',
            'next_token' => 'sometimes|string',
        ];
    }
}
