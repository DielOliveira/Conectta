<?php

namespace App\Enums;

enum OrdemServicoStatus: string
{
    case ABERTA = 'aberta';
    case ENVIADA = 'enviada';
    case ACEITA = 'aceita';
    case EM_ATENDIMENTO = 'em_atendimento';
    case AGUARDANDO_CORRECAO_CADASTRAL = 'aguardando_correcao_cadastral';
    case EM_CONFERENCIA = 'em_conferencia';
    case PENDENTE = 'pendente';
    case FINALIZADA = 'finalizada';
    case CANCELADA = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::ABERTA => 'Aberta', self::ENVIADA => 'Enviada', self::ACEITA => 'Aceita',
            self::EM_ATENDIMENTO => 'Em atendimento', self::AGUARDANDO_CORRECAO_CADASTRAL => 'Aguardando correção cadastral',
            self::EM_CONFERENCIA => 'Em conferência', self::PENDENTE => 'Pendente',
            self::FINALIZADA => 'Finalizada', self::CANCELADA => 'Cancelada',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::FINALIZADA, self::CANCELADA], true);
    }
}
