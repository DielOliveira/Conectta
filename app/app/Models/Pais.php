<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

#[Fillable([
    'iso2',
    'nome',
    'ddi',
    'order',
    'is_active',
])]
class Pais extends Model
{
    protected $table = 'paises';

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function telefoneOptions(): array
    {
        if (! Schema::hasTable('paises')) {
            return ['BR' => '+55 Brasil'];
        }

        return self::query()
            ->where('is_active', true)
            ->orderBy('order')
            ->orderBy('nome')
            ->get(['iso2', 'nome', 'ddi'])
            ->mapWithKeys(fn (Pais $pais): array => [
                $pais->iso2 => sprintf('+%s %s', $pais->ddi, $pais->nome),
            ])
            ->all();
    }

    public static function codigoTelefone(?string $isoOuDdi): string
    {
        $value = trim((string) $isoOuDdi);

        if ($value === '') {
            return '55';
        }

        if (ctype_digit($value)) {
            return $value;
        }

        if (! Schema::hasTable('paises')) {
            return strtoupper($value) === 'BR' ? '55' : '55';
        }

        return (string) (self::query()
            ->where('iso2', strtoupper($value))
            ->value('ddi') ?: '55');
    }

    public static function normalizarCodigoTelefone(?string $isoOuDdi): ?string
    {
        $value = trim((string) $isoOuDdi);

        if ($value === '') {
            return null;
        }

        $value = ltrim($value, '+');

        if (! Schema::hasTable('paises')) {
            return ctype_digit($value) ? ($value === '55' ? 'BR' : null) : strtoupper($value);
        }

        if (ctype_digit($value)) {
            return self::query()
                ->where('ddi', $value)
                ->orderByRaw("case when iso2 = 'BR' then 0 else 1 end")
                ->orderBy('order')
                ->value('iso2') ?: null;
        }

        $iso = strtoupper($value);

        return self::query()->where('iso2', $iso)->exists() ? $iso : null;
    }
}
