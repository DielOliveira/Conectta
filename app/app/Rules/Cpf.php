<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Cpf implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        if (strlen($digits) === 11 && $this->isValidCpf($digits)) {
            return;
        }

        $fail('Informe um CPF valido.');
    }

    private function isValidCpf(string $cpf): bool
    {
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($digit = 9; $digit < 11; $digit++) {
            $sum = 0;

            for ($position = 0; $position < $digit; $position++) {
                $sum += (int) $cpf[$position] * (($digit + 1) - $position);
            }

            $check = ((10 * $sum) % 11) % 10;

            if ((int) $cpf[$digit] !== $check) {
                return false;
            }
        }

        return true;
    }
}
