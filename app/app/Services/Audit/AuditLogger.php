<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * @param  array<string, mixed>|null  $antes
     * @param  array<string, mixed>|null  $depois
     * @param  array<string, mixed>|null  $contexto
     */
    public static function registrar(
        string $acao,
        string $descricao,
        ?Model $entidade = null,
        ?array $antes = null,
        ?array $depois = null,
        ?array $contexto = null,
        ?string $entidadeTipo = null,
        ?int $entidadeId = null,
    ): AuditLog {
        return AuditLog::query()->create([
            'user_id' => Auth::id(),
            'acao' => $acao,
            'entidade_tipo' => $entidadeTipo ?? self::tipoEntidade($entidade),
            'entidade_id' => $entidadeId ?? self::idEntidade($entidade),
            'descricao' => $descricao,
            'antes' => self::sanitizar($antes),
            'depois' => self::sanitizar($depois),
            'ip' => self::ip(),
            'user_agent' => self::userAgent(),
            'contexto' => self::sanitizar($contexto),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function snapshot(Model $model): array
    {
        return self::sanitizar($model->getAttributes()) ?? [];
    }

    private static function tipoEntidade(?Model $entidade): string
    {
        return $entidade ? class_basename($entidade) : 'sistema';
    }

    private static function idEntidade(?Model $entidade): ?int
    {
        $key = $entidade?->getKey();

        return is_numeric($key) ? (int) $key : null;
    }

    /**
     * @param  array<string, mixed>|null  $dados
     * @return array<string, mixed>|null
     */
    private static function sanitizar(?array $dados): ?array
    {
        if ($dados === null) {
            return null;
        }

        $sensibles = [
            'password',
            'remember_token',
            'token',
            'doc_token',
            'client_secret',
            'callback_secret',
            'secret',
            'signature',
        ];

        foreach ($dados as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            if (is_array($value)) {
                $dados[$key] = self::sanitizar($value);

                continue;
            }

            if (in_array($normalizedKey, $sensibles, true) || str_contains($normalizedKey, 'token') || str_contains($normalizedKey, 'secret')) {
                $dados[$key] = filled($value) ? '[redigido]' : $value;
            }
        }

        return Arr::undot($dados);
    }

    private static function ip(): ?string
    {
        try {
            return request()?->ip();
        } catch (\Throwable) {
            return null;
        }
    }

    private static function userAgent(): ?string
    {
        try {
            return request()?->userAgent();
        } catch (\Throwable) {
            return null;
        }
    }
}
