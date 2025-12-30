<?php

namespace App\Http\Requests\Auth;

use App\Rules\FunacEmail;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', new FunacEmail],
            'password' => ['required', 'string'],
            'app_name' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'O email é obrigatório.',
            'password.required' => 'A senha é obrigatória.',
            'app_name.required' => 'O identificador do aplicativo é obrigatório.',
        ];
    }
}
