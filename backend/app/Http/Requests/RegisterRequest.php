<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'department' => 'nullable|string|max:255',
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
            'name.required' => 'Ime je obavezno.',
            'name.max' => 'Ime može imati maksimalno 255 znakova.',
            'email.required' => 'Email je obavezan.',
            'email.email' => 'Email mora biti valjana email adresa.',
            'email.unique' => 'Ovaj email je već korišten.',
            'password.required' => 'Lozinka je obavezna.',
            'password.min' => 'Lozinka mora imati minimalno 8 znakova.',
            'password.confirmed' => 'Potvrda lozinke se ne podudara.',
            'department.max' => 'Odjel može imati maksimalno 255 znakova.',
        ];
    }
}
