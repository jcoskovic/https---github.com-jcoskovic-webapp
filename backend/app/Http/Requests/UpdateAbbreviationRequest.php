<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAbbreviationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'abbreviation' => 'sometimes|string|max:50',
            'meaning' => 'sometimes|string|max:255',
            'category' => 'sometimes|string|max:100',
        ];
    }

    /**
     * Get custom error messages.
     */
    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'abbreviation.max' => 'skraćenica može imati maksimalno 50 znakova.',
            'meaning.max' => 'Značenje može imati maksimalno 255 znakova.',
            'category.max' => 'Kategorija može imati maksimalno 100 znakova.',
        ];
    }
}
