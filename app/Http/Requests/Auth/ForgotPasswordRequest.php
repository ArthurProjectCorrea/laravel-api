<?php

namespace App\Http\Requests\Auth;

use App\Rules\FunacEmail;
use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', new FunacEmail],
            'frontend_url' => ['required', 'url'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'O email é obrigatório.',
            'frontend_url.required' => 'A URL do frontend é obrigatória.',
        ];
    }
}
