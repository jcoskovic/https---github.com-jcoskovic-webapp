<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportPdfRequest extends FormRequest
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
            'abbreviation_ids' => 'nullable|array',
            'abbreviation_ids.*' => 'integer|exists:abbreviations,id',
            'category' => 'nullable|string',
            'search' => 'nullable|string',
            'format' => 'in:simple,detailed',
        ];
    }
}
