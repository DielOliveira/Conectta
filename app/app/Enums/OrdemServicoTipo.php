<?php

namespace App\Enums;

enum OrdemServicoTipo: string
{
    case INSTALACAO = 'instalacao';
    case RETIRADA = 'retirada';
    case MANUTENCAO = 'manutencao';

    public function label(): string
    {
        return match ($this) {
            self::INSTALACAO => 'Instalação', self::RETIRADA => 'Retirada', self::MANUTENCAO => 'Manutenção'
        };
    }
}
