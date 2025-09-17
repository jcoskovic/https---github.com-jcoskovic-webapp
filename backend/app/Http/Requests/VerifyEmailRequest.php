<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyEmailRequest extends FormRequest
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
     */
    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'token' => 'required|string',
            'email' => 'required|email',
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
            'token.required' => 'Token je obavezan.',
            'token.string' => 'Token mora biti tekstualna vrijednost.',
            'email.required' => 'Email je obavezan.',
            'email.email' => 'Email mora biti valjana email adresa.',
        ];
    }
}
