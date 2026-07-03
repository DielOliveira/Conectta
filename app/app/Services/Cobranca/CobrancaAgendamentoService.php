<?php

namespace App\Services\Cobranca;

use App\Models\CobrancaAgendamento;
use Carbon\CarbonImmutable;

class CobrancaAgendamentoService
{
    public function __construct(
        private readonly CobrancaAutomaticaService $cobrancaService,
        private readonly CobrancaWhatsappService $whatsappService,
    ) {
    }

    /**
     * @return array{agendamentos:int,execucoes:int,erros:int}
     */
    public function processarVencidos(?CarbonImmutable $agora = null): array
    {
        $agora ??= CarbonImmutable::now();
        $contadores = ['agendamentos' => 0, 'execucoes' => 0, 'erros' => 0];

        foreach (CobrancaAgendamento::query()->where('ativo', true)->orderBy('horario')->get() as $agendamento) {
            $proxima = $agendamento->proxima_execucao_em
                ? CarbonImmutable::parse($agendamento->proxima_execucao_em)
                : null;

            if ($proxima === null) {
                $agendamento->forceFill([
                    'proxima_execucao_em' => $this->proximaExecucao($agendamento, $agora),
                ])->save();

                continue;
            }

            if ($proxima->greaterThan($agora)) {
                continue;
            }

            $contadores['agendamentos']++;

            try {
                $resultado = $this->executar($agendamento, manual: false, agora: $agora);
                $contadores['execucoes'] += count($resultado['execucao_ids']);
            } catch (\Throwable $exception) {
                $agendamento->forceFill([
                    'ultima_execucao_em' => $agora,
                    'proxima_execucao_em' => $this->proximaExecucao($agendamento, $agora->addMinute()),
                    'ultimo_status' => 'erro',
                    'ultima_mensagem' => $exception->getMessage(),
                ])->save();

                $contadores['erros']++;
            }
        }

        return $contadores;
    }

    /**
     * @return array{execucao_ids:array<int, int>,total_processados:int,total_enviados:int,total_ignorados:int,total_erros:int,dry_run:bool,whatsapp:array<string, int>|null}
     */
    public function executar(CobrancaAgendamento $agendamento, bool $manual = true, ?CarbonImmutable $agora = null): array
    {
        $agora ??= CarbonImmutable::now();

        $agendamento->refresh();

        $resultado = $this->cobrancaService->processar(
            data: $agora->startOfDay(),
            dryRun: (bool) $agendamento->dry_run,
            tipo: $agendamento->tipo,
            limit: $agendamento->limite,
            clienteId: null,
            agendamentoId: (int) $agendamento->id,
        );

        $whatsapp = null;

        if ($agendamento->enviar_whatsapp && $resultado['execucao_ids'] !== []) {
            $whatsapp = ['processados' => 0, 'enviados' => 0, 'simulados' => 0, 'erros' => 0];

            foreach ($resultado['execucao_ids'] as $execucaoId) {
                $resultadoWhatsapp = $this->whatsappService->enviarPendentes(
                    limit: $agendamento->limite,
                    envioId: null,
                    clienteId: null,
                    dryRun: (bool) $agendamento->dry_run,
                    execucaoId: $execucaoId,
                );

                foreach (array_keys($whatsapp) as $contador) {
                    $whatsapp[$contador] += $resultadoWhatsapp[$contador];
                }
            }
        }

        $status = $resultado['total_erros'] > 0 || ($whatsapp !== null && $whatsapp['erros'] > 0)
            ? 'erro'
            : 'concluido';

        $agendamento->forceFill([
            'ultima_execucao_em' => $agora,
            'proxima_execucao_em' => $manual ? $agendamento->proxima_execucao_em : $this->proximaExecucao($agendamento, $agora->addMinute()),
            'ultimo_status' => $status,
            'ultima_mensagem' => $this->mensagemResultado($resultado, $whatsapp),
            'ultima_cobranca_execucao_id' => $resultado['execucao_ids'][0] ?? null,
        ])->save();

        return [
            ...$resultado,
            'whatsapp' => $whatsapp,
        ];
    }

    public function recalcularProxima(CobrancaAgendamento $agendamento, ?CarbonImmutable $agora = null): void
    {
        $agora ??= CarbonImmutable::now();

        $agendamento->forceFill([
            'proxima_execucao_em' => $agendamento->ativo ? $this->proximaExecucao($agendamento, $agora) : null,
        ])->save();
    }

    public function proximaExecucao(CobrancaAgendamento $agendamento, CarbonImmutable $base): CarbonImmutable
    {
        $dias = $agendamento->dias_semana ?: array_keys(CobrancaAgendamento::DIAS_SEMANA);
        $horario = (string) $agendamento->horario;
        [$hora, $minuto] = array_map('intval', array_slice(explode(':', $horario), 0, 2));

        for ($offset = 0; $offset <= 14; $offset++) {
            $candidata = $base->startOfDay()->addDays($offset)->setTime($hora, $minuto);

            if (! in_array($candidata->dayOfWeek, array_map('intval', $dias), true)) {
                continue;
            }

            if ($candidata->greaterThan($base)) {
                return $candidata;
            }
        }

        return $base->addDay()->setTime($hora, $minuto);
    }

    private function mensagemResultado(array $resultado, ?array $whatsapp): string
    {
        $mensagem = sprintf(
            'Processados: %d. Enviados/pendentes: %d. Ignorados: %d. Erros: %d.',
            $resultado['total_processados'],
            $resultado['total_enviados'],
            $resultado['total_ignorados'],
            $resultado['total_erros'],
        );

        if ($whatsapp !== null) {
            $mensagem .= sprintf(
                ' WhatsApp - processados: %d, enviados: %d, simulados: %d, erros: %d.',
                $whatsapp['processados'],
                $whatsapp['enviados'],
                $whatsapp['simulados'],
                $whatsapp['erros'],
            );
        }

        return $mensagem;
    }
}
