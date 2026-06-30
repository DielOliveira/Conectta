<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CpfCnpj implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        if (strlen($digits) === 11 && $this->isValidCpf($digits)) {
            return;
        }

        if (strlen($digits) === 14 && $this->isValidCnpj($digits)) {
            return;
        }

        $fail('Informe um CPF ou CNPJ valido.');
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

    private function isValidCnpj(string $cnpj): bool
    {
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        $weights = [
            [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
            [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
        ];

        for ($digit = 12; $digit < 14; $digit++) {
            $sum = 0;

            foreach ($weights[$digit - 12] as $position => $weight) {
                $sum += (int) $cnpj[$position] * $weight;
            }

            $remainder = $sum % 11;
            $check = $remainder < 2 ? 0 : 11 - $remainder;

            if ((int) $cnpj[$digit] !== $check) {
                return false;
            }
        }

        return true;
    }
}
