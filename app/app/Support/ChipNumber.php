<?php

namespace App\Support;

use App\Models\Chip;
use Closure;

final class ChipNumber
{
    public const LOCAL_REGEX = '/^(?:1[1-9]|2[12478]|3[1-578]|4[1-9]|5[1345]|6[1-9]|7[134579]|8[1-9]|9[1-9])9\d{8}$/';

    public static function local(?string $number): string
    {
        $digits = preg_replace('/\D+/', '', (string) $number) ?? '';

        if (strlen($digits) === 13 && str_starts_with($digits, '55')) {
            return substr($digits, 2);
        }

        return $digits;
    }

    public static function canonical(?string $number): string
    {
        return '55'.self::local($number);
    }

    public static function uniqueRule(?int $ignoreId = null): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($ignoreId): void {
            $query = Chip::query()
                ->where('numero_chip', self::canonical((string) $value));

            if ($ignoreId !== null) {
                $query->whereKeyNot($ignoreId);
            }

            if ($query->exists()) {
                $fail('Este número de chip já está cadastrado.');
            }
        };
    }
}
