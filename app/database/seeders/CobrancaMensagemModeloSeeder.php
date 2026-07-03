<?php

namespace Database\Seeders;

use App\Models\CobrancaMensagemModelo;
use App\Services\Cobranca\CobrancaAutomaticaService;
use Illuminate\Database\Seeder;

class CobrancaMensagemModeloSeeder extends Seeder
{
    public function run(): void
    {
        $modelos = [
            CobrancaAutomaticaService::BOLETO_7_DIAS => [
                'nome' => 'Boleto 7 dias antes',
                'conteudo' => "Ola, {cliente_nome}\nAqui e da Conectta Rastreamento. Tudo bem?\n\nEstou passando para enviar seu boleto com vencimento em {vencimento}.\n\nValor do boleto: {valor}\n\nAtenciosamente,\nConectta Rastreamento",
            ],
            CobrancaAutomaticaService::LEMBRETE_VENCIMENTO => [
                'nome' => 'Lembrete no vencimento',
                'conteudo' => "Ola, {cliente_nome}\nAqui e da Conectta Rastreamento. Tudo bem?\n\nEstou passando para lembrar que seu vencimento e hoje.\n\nValor: {valor}\n\nAtenciosamente,\nConectta Rastreamento",
            ],
        ];

        foreach (CobrancaAutomaticaService::DIAS_ATRASO as $dias) {
            $modelos['atraso_'.$dias] = [
                'nome' => 'Atraso '.$dias.' dias',
                'conteudo' => "Ola, {cliente_nome}\nAqui e da Conectta Rastreamento. Tudo bem?\n\nEstou passando para lembrar que seu boleto esta vencido ha {dias_atraso} dias. Para nao ter o servico de rastreamento suspenso, segue abaixo seu boleto para pagamento.\n\nValor do boleto: {valor}\n\nAtenciosamente,\nConectta Rastreamento",
            ];
        }

        $modelos['pix_instrucao'] = [
            'nome' => 'Instrucao PIX',
            'conteudo' => 'Caso prefira pagar com PIX, segue o codigo copia e cola:',
        ];

        $modelos['finalizacao'] = [
            'nome' => 'Finalizacao',
            'conteudo' => "Atendimento finalizado\n\nEstou finalizando nossa interacao, qualquer duvida estou a disposicao",
        ];

        foreach ($modelos as $tipo => $dados) {
            CobrancaMensagemModelo::query()->updateOrCreate(
                [
                    'tipo' => $tipo,
                    'canal' => 'whatsapp',
                    'ordem' => 10,
                ],
                [
                    'nome' => $dados['nome'],
                    'conteudo' => $dados['conteudo'],
                    'ativo' => true,
                ],
            );
        }
    }
}
