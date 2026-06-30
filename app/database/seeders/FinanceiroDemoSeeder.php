<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Invoice;
use App\Models\Lancamento;
use Illuminate\Database\Seeder;

class FinanceiroDemoSeeder extends Seeder
{
    public function run(): void
    {
        $clientes = Cliente::query()
            ->orderBy('id')
            ->limit(8)
            ->get();

        if ($clientes->isEmpty()) {
            return;
        }

        $mesAtual = (int) now()->month;
        $anoAtual = (int) now()->year;
        $proximoMes = now()->addMonth();
        $statusBoletos = ['Aguardando Pagamento', 'Pago', 'Atrasado', 'Cancelado', 'Processando'];
        $hashIds = [
            '2982663755efef01dd3d23c23ee964fe:92879416187d0bbb19b7827aedb7479153c9e79b5c6c7d76d97824f4ddde8a5e',
            '7df8c2685d3bb517138cb74dd90cad71:061870941970bf299c7b78fd123292f7d82c7b45cab746a073c9e4000a3799d8',
            '3bd58922e2615bfc37486b4705840479:a297387a8ab66f898b6a492528d7a76479b2826bdcfba76c3dc8e68a61af4446',
            'd9c2196bd87d96912a75cae2ed3fac2a:b798f49cb978766d0edfd112f9bc2281548592be97ad7f636f15309a174591db',
            'c7e63104578b8912e8c8c5780564acea:0d9043f1103f2c641ae4f4d3a9947493d4413670c05071affc8bff5af0021c0c',
            'a8306d84465431e0e1225011077a94c6:08d1495b6e876b849baa643f8e54f9886a69922eddc58efbc3d9fd1653dafee6',
            'd81f7ba78116e5d60c49bd4aac6430d5:380c43b4c35ee944ee0852d680cf22f3f09e3602a6b8b9c891740fde36942e04',
            '2071288c55f42b40d8d301d52b937804:9c60b5800e22ed8067396ea18061ba1906ce895b59114f8de0dbfcaefc8512d4',
            '1cf38fb92e01d061b6d693c8fb070a1c:71b8804c6c63a98d3b82acdd196ff813898e0f7a4d435743ef5bcce3944453ae',
            '9c1f4cce1bf5c42c5e58625ff0a6a1d4:7abf1c97f6ccd8c4c5a157dcf97e1877276d1c7cdc1f646a63564e0ad2c09232',
            '51365f2742d28b4d6da12d06e6886e95:6862560efaa73d8c548b95360ce3c3fad49ea3c6bf2fc95a9eec497cee98cd25',
            '682ebc77651cbbf0939af5ff7bdc5dad:898d1c3bf46b1dfd4ae7c7eb3a1233803f6a1ce5bf06e88d527d1d772328ce4e',
            '8fd83166fd8aa2d130d30a17aedac4d7:18808dcb3748aeb91a47414cdd7e3cfd9519e12ebc392abf9f0827e19ada5423',
            'b430e5a0ed5cca60e24614779c634458:7793ee7fe036ea927cb3f1b3dac427d760bccca90a6910c2b76acab0c1ffd720',
            '2f38fe823e1138085e7bae7ec75b6445:667438712034c03d07a505bc9198d22e04851d704102aa6d1bfb6a6d8b677c6c',
            '8968a248945774a57e25a375c17a77b5:bf00cb7857c2f69515a555625fa60dd45e5936fe5721b057c03c342c912ef03a',
            'fe80a1a20f83dcb91b089126b9464701:00ddfaeaa9abb849556f9ec60b0d7664eaded14eaced461db1d8017486bb16cb',
            'f939d7dd0b2355e96f616c8dd7c750f0:26c09851271e520eee6d65d086199681ad0374c1976a42aea279c6874cde1aa7',
            '3341318627175299f19ab29b21868e2a:87c5318c1ccc680ce17b63dac9f8ed3559494a9b9172e21b20a07117f959838b',
            '4b854087a5c382fa616f5eb64e58ee62:7e177a8a98e65b88c7e9a13a730c9d2c242760b8236fb776e3597fb26b80bb',
        ];

        Lancamento::query()
            ->where('numero_boleto', 'like', 'Demo %')
            ->delete();

        foreach ($clientes as $index => $cliente) {
            $valor = 50 + ($index * 10);

            $lancamentoAtual = Lancamento::query()->updateOrCreate(
                [
                    'cliente_id' => $cliente->id,
                    'mes_referencia' => $mesAtual,
                    'ano_referencia' => $anoAtual,
                    'numero_boleto' => 'Lytex',
                ],
                [
                    'data_lancamento' => now()->toDateString(),
                    'valor_planejado' => $valor,
                    'valor_efetivado' => $index % 2 === 0 ? $valor : null,
                    'observacao' => $index === 0 ? 'pagamento agendado' : null,
                    'is_baixado' => $index % 2 === 0,
                    'time_stamp' => now(),
                ],
            );

            Invoice::query()->updateOrCreate(
                ['lancamento_id' => $lancamentoAtual->id],
                [
                    'client_id' => (string) $cliente->id,
                    'cpf_cnpj' => $cliente->cpf_cnpj,
                    'fatura_id' => 'DEMO-' . $lancamentoAtual->id,
                    'hash_id' => $hashIds[$index % count($hashIds)],
                    'total_value' => $valor,
                    'status' => $statusBoletos[$index % count($statusBoletos)],
                    'vencimento' => now()->startOfMonth()->addDays((int) $cliente->dia_pagamento)->toDateTimeString(),
                ],
            );

            if ($index % 3 === 0) {
                $lancamentoProximo = Lancamento::query()->updateOrCreate(
                    [
                        'cliente_id' => $cliente->id,
                        'mes_referencia' => (int) $proximoMes->month,
                        'ano_referencia' => (int) $proximoMes->year,
                        'numero_boleto' => 'Lytex',
                    ],
                    [
                        'data_lancamento' => $proximoMes->toDateString(),
                        'valor_planejado' => $valor,
                        'valor_efetivado' => null,
                        'observacao' => null,
                        'is_baixado' => false,
                        'time_stamp' => now(),
                    ],
                );

                Invoice::query()->updateOrCreate(
                    ['lancamento_id' => $lancamentoProximo->id],
                    [
                        'client_id' => (string) $cliente->id,
                        'cpf_cnpj' => $cliente->cpf_cnpj,
                        'fatura_id' => 'DEMO-' . $lancamentoProximo->id,
                        'hash_id' => $hashIds[($index + $clientes->count()) % count($hashIds)],
                        'total_value' => $valor,
                        'status' => $statusBoletos[($index + 2) % count($statusBoletos)],
                        'vencimento' => $proximoMes->startOfMonth()->addDays((int) $cliente->dia_pagamento)->toDateTimeString(),
                    ],
                );
            }
        }

        foreach ($statusBoletos as $index => $status) {
            $cliente = $clientes[$index % $clientes->count()];
            $valor = 90 + ($index * 25);

            $lancamentoStatus = Lancamento::query()->updateOrCreate(
                [
                    'cliente_id' => $cliente->id,
                    'mes_referencia' => $mesAtual,
                    'ano_referencia' => $anoAtual,
                    'numero_boleto' => 'Demo ' . $status,
                ],
                [
                    'data_lancamento' => now()->toDateString(),
                    'valor_planejado' => $valor,
                    'valor_efetivado' => 0,
                    'observacao' => 'Lancamento demo para status ' . $status,
                    'is_baixado' => false,
                    'time_stamp' => now(),
                ],
            );

            Invoice::query()->updateOrCreate(
                ['lancamento_id' => $lancamentoStatus->id],
                [
                    'client_id' => (string) $cliente->id,
                    'cpf_cnpj' => $cliente->cpf_cnpj,
                    'fatura_id' => 'STATUS-' . $lancamentoStatus->id,
                    'hash_id' => $hashIds[($index + 12) % count($hashIds)],
                    'total_value' => $valor,
                    'status' => $status,
                    'vencimento' => now()->startOfMonth()->addDays((int) $cliente->dia_pagamento)->toDateTimeString(),
                ],
            );
        }
    }
}
