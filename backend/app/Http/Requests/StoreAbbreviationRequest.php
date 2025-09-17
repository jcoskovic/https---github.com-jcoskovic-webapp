<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAbbreviationRequest extends FormRequest
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
            'abbreviation' => 'required|string|max:50',
            'meaning' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|string|max:100',
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
            'abbreviation.required' => 'skraćenica je obavezna.',
            'abbreviation.max' => 'skraćenica može imati maksimalno 50 znakova.',
            'meaning.required' => 'Značenje je obavezno.',
            'meaning.max' => 'Značenje može imati maksimalno 255 znakova.',
            'description.max' => 'Opis može imati maksimalno 1000 znakova.',
            'category.required' => 'Kategorija je obavezna.',
            'category.max' => 'Kategorija može imati maksimalno 100 znakova.',
        ];
    }
}
