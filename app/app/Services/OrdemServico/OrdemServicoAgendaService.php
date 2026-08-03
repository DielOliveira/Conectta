<?php

namespace App\Services\OrdemServico;

use App\Models\OrdemServico;
use App\Models\OrdemServicoDisponibilidade;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class OrdemServicoAgendaService
{
    public const DURACAO_MINUTOS = 40;

    public function criarDisponibilidade(int $tecnicoId, string $data, string $horaInicio, string $horaFim): OrdemServicoDisponibilidade
    {
        $inicio = CarbonImmutable::parse("{$data} {$horaInicio}");
        $fim = CarbonImmutable::parse("{$data} {$horaFim}");
        if ($inicio->isPast() || $fim->lessThanOrEqualTo($inicio) || $inicio->diffInMinutes($fim) < self::DURACAO_MINUTOS) {
            throw ValidationException::withMessages(['hora_inicio' => 'Informe um intervalo futuro com pelo menos 40 minutos.']);
        }

        $sobrepoe = OrdemServicoDisponibilidade::query()->where('tecnico_id', $tecnicoId)->whereDate('data', $data)
            ->where('hora_inicio', '<', $fim->format('H:i:s'))->where('hora_fim', '>', $inicio->format('H:i:s'))->exists();
        if ($sobrepoe) {
            throw ValidationException::withMessages(['hora_inicio' => 'Este intervalo se sobrepõe a outra disponibilidade do técnico.']);
        }

        return OrdemServicoDisponibilidade::query()->create([
            'tecnico_id' => $tecnicoId, 'data' => $data,
            'hora_inicio' => $inicio->format('H:i:s'), 'hora_fim' => $fim->format('H:i:s'),
        ]);
    }

    /** @return Collection<int, CarbonImmutable> */
    public function blocos(OrdemServicoDisponibilidade $disponibilidade, ?int $ignorarOrdemId = null): Collection
    {
        $inicio = CarbonImmutable::parse($disponibilidade->data->format('Y-m-d').' '.$disponibilidade->hora_inicio);
        $fim = CarbonImmutable::parse($disponibilidade->data->format('Y-m-d').' '.$disponibilidade->hora_fim);
        $ocupados = OrdemServico::query()->where('disponibilidade_id', $disponibilidade->id)
            ->whereNotNull('agendado_em')->when($ignorarOrdemId, fn ($q) => $q->whereKeyNot($ignorarOrdemId))
            ->whereNotIn('status', ['aberta', 'cancelada', 'finalizada'])->pluck('agendado_em')
            ->map(fn ($data) => CarbonImmutable::parse($data)->format('Y-m-d H:i:s'))->all();

        $blocos = collect();
        while ($inicio->addMinutes(self::DURACAO_MINUTOS)->lessThanOrEqualTo($fim)) {
            if (! in_array($inicio->format('Y-m-d H:i:s'), $ocupados, true)) {
                $blocos->push($inicio);
            }
            $inicio = $inicio->addMinutes(self::DURACAO_MINUTOS);
        }

        return $blocos;
    }

    public function validarBloco(OrdemServicoDisponibilidade $disponibilidade, CarbonImmutable $horario, ?int $ignorarOrdemId = null): void
    {
        if ($horario->isPast() || ! $this->blocos($disponibilidade, $ignorarOrdemId)->contains(fn (CarbonImmutable $bloco) => $bloco->equalTo($horario))) {
            throw ValidationException::withMessages(['agendado_em' => 'O bloco escolhido não está disponível.']);
        }
    }
}
