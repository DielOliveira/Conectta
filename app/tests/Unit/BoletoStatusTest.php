<?php

namespace Tests\Unit;

use App\Support\BoletoStatus;
use PHPUnit\Framework\TestCase;

class BoletoStatusTest extends TestCase
{
    public function test_agrupa_variantes_sem_duplicar_as_opcoes(): void
    {
        $opcoes = BoletoStatus::opcoes([
            'Pago',
            'paid',
            'Cancelado',
            'canceled',
            'Atrasado',
            'overdue',
            'Processando',
            'processing',
            'Aguardando Pagamento',
            'waitingPayment',
        ]);

        $this->assertSame([
            ['value' => 'waiting_payment', 'label' => 'Aguardando Pagamento'],
            ['value' => 'overdue', 'label' => 'Atrasado'],
            ['value' => 'canceled', 'label' => 'Cancelado'],
            ['value' => 'paid', 'label' => 'Pago'],
            ['value' => 'processing', 'label' => 'Processando'],
        ], $opcoes);
    }

    public function test_filtro_inclui_variantes_historicas_e_atuais(): void
    {
        $this->assertSame(['Pago', 'paid'], BoletoStatus::valoresDoFiltro('paid'));
        $this->assertSame(['Cancelado', 'canceled', 'cancelled'], BoletoStatus::valoresDoFiltro('canceled'));
        $this->assertSame(
            ['Aguardando Pagamento', 'waitingPayment', 'waiting_payment', 'waiting', 'pending'],
            BoletoStatus::valoresDoFiltro('waiting_payment'),
        );
    }
}
