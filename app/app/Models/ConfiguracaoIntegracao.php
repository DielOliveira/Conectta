<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'integracao',
    'ambiente',
    'base_url',
    'client_id',
    'client_secret',
    'callback_secret',
    'token',
    'auth_scheme',
    'timeout',
    'template_principal_id',
    'template_aditivo_id',
    'template_comodato_id',
    'ativo',
])]
class ConfiguracaoIntegracao extends Model
{
    protected $table = 'configuracoes_integracao';

    protected function casts(): array
    {
        return [
            'client_secret' => 'encrypted',
            'callback_secret' => 'encrypted',
            'token' => 'encrypted',
            'timeout' => 'integer',
            'ativo' => 'boolean',
        ];
    }


    public static function zapsignAtiva(): self
    {
        $ativa = self::query()
            ->where('integracao', 'zapsign')
            ->where('ativo', true)
            ->first();

        if ($ativa) {
            return $ativa;
        }

        $producao = self::zapsignAmbiente('producao');
        $producao->forceFill(['ativo' => true])->save();

        return $producao;
    }

    public static function zapsignAmbiente(string $ambiente): self
    {
        $temAmbienteAtivo = self::query()
            ->where('integracao', 'zapsign')
            ->where('ativo', true)
            ->exists();

        return self::query()->firstOrCreate(
            [
                'integracao' => 'zapsign',
                'ambiente' => $ambiente,
            ],
            [
                'base_url' => 'https://api.zapsign.com.br',
                'auth_scheme' => 'Bearer',
                'timeout' => 30,
                'template_principal_id' => $ambiente === 'producao' ? 'e8db8e22-f163-419e-898f-d7709fea2296' : null,
                'template_aditivo_id' => $ambiente === 'producao' ? '029c6d29-7c8d-4e9c-8f8e-9bdc16b7add8' : null,
                'template_comodato_id' => $ambiente === 'producao' ? 'fe0d3df0-b615-4296-81d7-0dd274036892' : null,
                'ativo' => $ambiente === 'producao' && ! $temAmbienteAtivo,
            ],
        );
    }
    public static function lytex(): self
    {
        return self::lytexAtiva();
    }

    public static function lytexAtiva(): self
    {
        $ativa = self::query()
            ->where('integracao', 'lytex')
            ->where('ativo', true)
            ->first();

        if ($ativa) {
            return $ativa;
        }

        $producao = self::lytexAmbiente('producao');
        $producao->forceFill(['ativo' => true])->save();

        return $producao;
    }

    public static function lytexAmbiente(string $ambiente): self
    {
        $temAmbienteAtivo = self::query()
            ->where('integracao', 'lytex')
            ->where('ativo', true)
            ->exists();

        return self::query()->firstOrCreate(
            [
                'integracao' => 'lytex',
                'ambiente' => $ambiente,
            ],
            [
                'base_url' => config('services.lytex.base_url', 'https://api-pay.lytex.com.br'),
                'client_id' => $ambiente === 'producao' ? config('services.lytex.client_id') : null,
                'client_secret' => $ambiente === 'producao' ? config('services.lytex.client_secret') : null,
                'auth_scheme' => config('services.lytex.auth_scheme', 'Bearer'),
                'timeout' => (int) config('services.lytex.timeout', 30),
                'ativo' => $ambiente === 'producao' && ! $temAmbienteAtivo,
            ],
        );
    }
}
