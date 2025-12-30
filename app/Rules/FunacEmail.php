<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class FunacEmail implements Rule
{
    public function __construct()
    {
        //
    }

    public function passes($attribute, $value): bool
    {
        return is_string($value) && str_ends_with(strtolower($value), '@funac.mt.gov.br');
    }

    public function message(): string
    {
        return 'O domínio do email não é permitido.';
    }
}
