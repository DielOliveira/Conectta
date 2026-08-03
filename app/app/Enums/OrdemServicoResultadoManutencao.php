<?php

namespace App\Enums;

enum OrdemServicoResultadoManutencao: string
{
    case REPARO_SEM_TROCA = 'reparo_sem_troca';
    case TROCA_RASTREADOR = 'troca_rastreador';
    case TROCA_CHIP = 'troca_chip';
    case TROCA_RASTREADOR_CHIP = 'troca_rastreador_chip';
    case SEM_DEFEITO = 'sem_defeito';
}
