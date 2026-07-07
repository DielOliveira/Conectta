<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['nome', 'label', 'modulo', 'acao', 'ordem'])]
class Permission extends Model
{
    public const BOLETOS_BAIXAR = 'Boletos_Baixar';
    public const BOLETOS_ESCRITA = 'Boletos_Escrita';
    public const BOLETOS_LEITURA = 'Boletos_Leitura';
    public const CADASTRO_ESCRITA = 'Cadastro_Escrita';
    public const CADASTRO_EXCLUSAO = 'Cadastro_Exclusao';
    public const CADASTRO_LEITURA = 'Cadastro_Leitura';
    public const COORDENADOR = 'Coordenador';
    public const ESTOQUE_ESCRITA = 'Estoque_Escrita';
    public const ESTOQUE_LEITURA = 'Estoque_Leitura';
    public const FATURAMENTO_ESCRITA = 'Faturamento_Escrita';
    public const FATURAMENTO_LEITURA = 'Faturamento_Leitura';
    public const FINANCEIRO_ESCRITA = 'Financeiro_Escrita';
    public const FINANCEIRO_LEITURA = 'Financeiro_Leitura';
    public const TECNICO = 'Tecnico';

    public static function catalogo(): array
    {
        return [
            self::BOLETOS_BAIXAR => ['label' => 'Boletos - Baixar', 'modulo' => 'Boletos', 'acao' => 'Baixar', 'ordem' => 10],
            self::BOLETOS_ESCRITA => ['label' => 'Boletos - Escrita', 'modulo' => 'Boletos', 'acao' => 'Escrita', 'ordem' => 20],
            self::BOLETOS_LEITURA => ['label' => 'Boletos - Leitura', 'modulo' => 'Boletos', 'acao' => 'Leitura', 'ordem' => 30],
            self::CADASTRO_ESCRITA => ['label' => 'Cadastro - Escrita', 'modulo' => 'Cadastro', 'acao' => 'Escrita', 'ordem' => 40],
            self::CADASTRO_EXCLUSAO => ['label' => 'Cadastro - Exclusao', 'modulo' => 'Cadastro', 'acao' => 'Exclusao', 'ordem' => 50],
            self::CADASTRO_LEITURA => ['label' => 'Cadastro - Leitura', 'modulo' => 'Cadastro', 'acao' => 'Leitura', 'ordem' => 60],
            self::COORDENADOR => ['label' => 'Coordenador', 'modulo' => 'Administrativo', 'acao' => 'Coordenador', 'ordem' => 70],
            self::ESTOQUE_ESCRITA => ['label' => 'Estoque - Escrita', 'modulo' => 'Estoque', 'acao' => 'Escrita', 'ordem' => 80],
            self::ESTOQUE_LEITURA => ['label' => 'Estoque - Leitura', 'modulo' => 'Estoque', 'acao' => 'Leitura', 'ordem' => 90],
            self::FATURAMENTO_ESCRITA => ['label' => 'Faturamento - Escrita', 'modulo' => 'Faturamento', 'acao' => 'Escrita', 'ordem' => 100],
            self::FATURAMENTO_LEITURA => ['label' => 'Faturamento - Leitura', 'modulo' => 'Faturamento', 'acao' => 'Leitura', 'ordem' => 110],
            self::FINANCEIRO_ESCRITA => ['label' => 'Financeiro - Escrita', 'modulo' => 'Financeiro', 'acao' => 'Escrita', 'ordem' => 120],
            self::FINANCEIRO_LEITURA => ['label' => 'Financeiro - Leitura', 'modulo' => 'Financeiro', 'acao' => 'Leitura', 'ordem' => 130],
            self::TECNICO => ['label' => 'Técnico', 'modulo' => 'Administrativo', 'acao' => 'Tecnico', 'ordem' => 140],
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
