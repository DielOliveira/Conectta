<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'integracao',
    'ambiente',
    'driver',
    'base_url',
    'client_id',
    'japi_sessao_cobrancas',
    'japi_sessao_os_campo',
    'japi_sessao_os_manutencao',
    'client_secret',
    'callback_secret',
    'token',
    'auth_scheme',
    'timeout',
    'template_principal_id',
    'template_aditivo_id',
    'template_comodato_id',
    'pix_endpoint',
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

    public static function zapiAtiva(): self
    {
        $ativa = self::query()
            ->where('integracao', 'zapi')
            ->where('ativo', true)
            ->first();

        if ($ativa) {
            return $ativa;
        }

        $producao = self::zapiAmbiente('producao');
        $producao->forceFill(['ativo' => true])->save();

        return $producao;
    }

    public static function zapiAmbiente(string $ambiente): self
    {
        $temAmbienteAtivo = self::query()
            ->where('integracao', 'zapi')
            ->where('ativo', true)
            ->exists();

        return self::query()->firstOrCreate(
            [
                'integracao' => 'zapi',
                'ambiente' => $ambiente,
            ],
            [
                'base_url' => config('services.whatsapp.zapi.base_url', 'https://api.z-api.io'),
                'client_id' => $ambiente === 'producao' ? config('services.whatsapp.zapi.instance_id') : null,
                'token' => $ambiente === 'producao' ? config('services.whatsapp.zapi.token') : null,
                'client_secret' => $ambiente === 'producao' ? config('services.whatsapp.zapi.client_token') : null,
                'timeout' => (int) config('services.whatsapp.zapi.timeout', 30),
                'pix_endpoint' => config('services.whatsapp.zapi.pix_endpoint', 'send-button-pix'),
                'ativo' => $ambiente === 'producao' && ! $temAmbienteAtivo,
            ],
        );
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

    public static function whatsapp(): self
    {
        return self::query()->firstOrCreate(
            ['integracao' => 'whatsapp', 'ambiente' => 'global'],
            ['driver' => config('services.whatsapp.driver', 'zapi'), 'ativo' => true],
        );
    }

    public static function whatsappDriver(): string
    {
        $driver = (string) self::whatsapp()->driver;

        return in_array($driver, ['zapi', 'japi'], true) ? $driver : 'zapi';
    }

    public static function japiAtiva(): self
    {
        $ativa = self::query()->where('integracao', 'japi')->where('ativo', true)->first();

        if ($ativa) {
            return $ativa;
        }

        $producao = self::japiAmbiente('producao');
        $producao->forceFill(['ativo' => true])->save();

        return $producao;
    }

    public static function japiAmbiente(string $ambiente): self
    {
        $temAmbienteAtivo = self::query()->where('integracao', 'japi')->where('ativo', true)->exists();

        return self::query()->firstOrCreate(
            ['integracao' => 'japi', 'ambiente' => $ambiente],
            [
                'base_url' => config('services.whatsapp.japi.base_url', 'http://127.0.0.1:3001'),
                'client_id' => config('services.whatsapp.japi.session', 'default'),
                'japi_sessao_cobrancas' => config('services.whatsapp.japi.session', 'default'),
                'japi_sessao_os_campo' => config('services.whatsapp.japi.session', 'default'),
                'japi_sessao_os_manutencao' => config('services.whatsapp.japi.session', 'default'),
                'timeout' => (int) config('services.whatsapp.japi.timeout', 60),
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
