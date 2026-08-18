<?php

namespace App\Services\OrdemServico;

use App\Models\OrdemServico;
use App\Models\OrdemServicoDisponibilidade;
use App\Models\Tecnico;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrdemServicoAgendaService
{
    public const DURACAO_MINUTOS = 60;

    public function criarDisponibilidade(int $tecnicoId, string $data, string $horaInicio, string $horaFim): OrdemServicoDisponibilidade
    {
        return $this->criarIntervalo($tecnicoId, $data, $horaInicio, $horaFim, OrdemServicoDisponibilidade::TIPO_DISPONIBILIDADE);
    }

    public function criarIntervalo(int $tecnicoId, string $data, string $horaInicio, string $horaFim, string $tipo): OrdemServicoDisponibilidade
    {
        [$inicio, $fim] = $this->validarIntervalo($tecnicoId, $data, $horaInicio, $horaFim, $tipo);

        return OrdemServicoDisponibilidade::query()->create([
            'tecnico_id' => $tecnicoId, 'tipo' => $tipo, 'data' => $data,
            'hora_inicio' => $inicio->format('H:i:s'), 'hora_fim' => $fim->format('H:i:s'),
        ]);
    }

    /** @return Collection<int, OrdemServicoDisponibilidade> */
    public function criarSemana(int $tecnicoId, string $dataReferencia, string $horaInicio, string $horaFim, string $tipo): Collection
    {
        $inicioSemana = CarbonImmutable::parse($dataReferencia)->startOfWeek(CarbonInterface::MONDAY);
        $datas = collect(range(0, 4))
            ->map(fn (int $dia): string => $inicioSemana->addDays($dia)->toDateString())
            ->filter(fn (string $data): bool => CarbonImmutable::parse("{$data} {$horaInicio}")->isFuture())
            ->values();

        if ($datas->isEmpty()) {
            throw ValidationException::withMessages(['data' => 'Esta semana não possui mais dias úteis futuros para o horário informado.']);
        }

        return DB::transaction(function () use ($tecnicoId, $datas, $horaInicio, $horaFim, $tipo): Collection {
            $datas->each(fn (string $data) => $this->validarIntervalo($tecnicoId, $data, $horaInicio, $horaFim, $tipo));

            return $datas->map(fn (string $data): OrdemServicoDisponibilidade => OrdemServicoDisponibilidade::query()->create([
                'tecnico_id' => $tecnicoId,
                'tipo' => $tipo,
                'data' => $data,
                'hora_inicio' => CarbonImmutable::parse($horaInicio)->format('H:i:s'),
                'hora_fim' => CarbonImmutable::parse($horaFim)->format('H:i:s'),
            ]));
        });
    }

    public function atualizarDisponibilidade(OrdemServicoDisponibilidade $disponibilidade, int $tecnicoId, string $data, string $horaInicio, string $horaFim): OrdemServicoDisponibilidade
    {
        return DB::transaction(function () use ($disponibilidade, $tecnicoId, $data, $horaInicio, $horaFim): OrdemServicoDisponibilidade {
            $disponibilidade = OrdemServicoDisponibilidade::query()->lockForUpdate()->findOrFail($disponibilidade->id);
            [$inicio, $fim] = $this->validarIntervalo($tecnicoId, $data, $horaInicio, $horaFim, $disponibilidade->tipo, $disponibilidade->id);

            $ordensOcupando = $disponibilidade->ordens()->whereNotNull('agendado_em')->get();
            foreach ($ordensOcupando as $ordem) {
                $agendado = CarbonImmutable::parse($ordem->agendado_em);
                $preservaBloco = $tecnicoId === (int) $disponibilidade->tecnico_id
                    && $agendado->isSameDay($inicio)
                    && $agendado->greaterThanOrEqualTo($inicio)
                    && $agendado->addMinutes(self::DURACAO_MINUTOS)->lessThanOrEqualTo($fim)
                    && $inicio->diffInMinutes($agendado) % self::DURACAO_MINUTOS === 0;
                if (! $preservaBloco) {
                    throw ValidationException::withMessages(['hora_inicio' => 'A alteração removeria um bloco já usado por uma OS. A data, o técnico e os horários desse atendimento precisam ser preservados.']);
                }
            }

            $disponibilidade->update(['tecnico_id' => $tecnicoId, 'data' => $data, 'hora_inicio' => $inicio->format('H:i:s'), 'hora_fim' => $fim->format('H:i:s')]);

            return $disponibilidade->fresh();
        });
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    private function validarIntervalo(int $tecnicoId, string $data, string $horaInicio, string $horaFim, string $tipo, ?int $ignorarId = null): array
    {
        if (! in_array($tipo, [OrdemServicoDisponibilidade::TIPO_DISPONIBILIDADE, OrdemServicoDisponibilidade::TIPO_BLOQUEIO], true)) {
            throw ValidationException::withMessages(['tipo' => 'Selecione disponibilidade ou bloqueio.']);
        }

        $tecnico = Tecnico::query()->findOrFail($tecnicoId);
        if ($tipo === OrdemServicoDisponibilidade::TIPO_DISPONIBILIDADE && strlen(preg_replace('/\D+/', '', (string) $tecnico->telefone) ?? '') < 10) {
            throw ValidationException::withMessages(['tecnico_id' => 'O técnico precisa ter um telefone válido antes de receber disponibilidades na agenda. Corrija o cadastro do técnico.']);
        }

        $inicio = CarbonImmutable::parse("{$data} {$horaInicio}");
        $fim = CarbonImmutable::parse("{$data} {$horaFim}");
        if ($inicio->isPast() || $fim->lessThanOrEqualTo($inicio) || $inicio->diffInMinutes($fim) < self::DURACAO_MINUTOS) {
            throw ValidationException::withMessages(['hora_inicio' => 'Informe um intervalo futuro com pelo menos 1 hora.']);
        }

        $sobrepoe = OrdemServicoDisponibilidade::query()->where('tecnico_id', $tecnicoId)->whereDate('data', $data)->where('tipo', $tipo)
            ->when($ignorarId, fn ($query) => $query->whereKeyNot($ignorarId))
            ->where('hora_inicio', '<', $fim->format('H:i:s'))->where('hora_fim', '>', $inicio->format('H:i:s'))->exists();
        if ($sobrepoe) {
            throw ValidationException::withMessages(['hora_inicio' => $tipo === OrdemServicoDisponibilidade::TIPO_BLOQUEIO
                ? 'Este intervalo se sobrepõe a outro bloqueio do técnico.'
                : 'Este intervalo se sobrepõe a outra disponibilidade do técnico.']);
        }

        if ($tipo === OrdemServicoDisponibilidade::TIPO_BLOQUEIO) {
            $possuiOs = OrdemServico::query()
                ->where('tecnico_id', $tecnicoId)
                ->whereDate('agendado_em', $data)
                ->whereNotIn('status', ['aberta', 'cancelada', 'finalizada'])
                ->get(['agendado_em'])
                ->contains(function (OrdemServico $ordem) use ($inicio, $fim): bool {
                    $agendado = CarbonImmutable::parse($ordem->agendado_em);

                    return $agendado->lessThan($fim) && $agendado->addMinutes(self::DURACAO_MINUTOS)->greaterThan($inicio);
                });
            if ($possuiOs) {
                throw ValidationException::withMessages(['hora_inicio' => 'Este período já possui uma OS agendada e não pode ser bloqueado.']);
            }
        }

        return [$inicio, $fim];
    }

    /** @return Collection<int, CarbonImmutable> */
    public function blocos(OrdemServicoDisponibilidade $disponibilidade, ?int $ignorarOrdemId = null): Collection
    {
        if ($disponibilidade->isBloqueio()) {
            return collect();
        }

        $inicio = CarbonImmutable::parse($disponibilidade->data->format('Y-m-d').' '.$disponibilidade->hora_inicio);
        $fim = CarbonImmutable::parse($disponibilidade->data->format('Y-m-d').' '.$disponibilidade->hora_fim);
        $ocupados = OrdemServico::query()->where('disponibilidade_id', $disponibilidade->id)
            ->whereNotNull('agendado_em')->when($ignorarOrdemId, fn ($q) => $q->whereKeyNot($ignorarOrdemId))
            ->whereNotIn('status', ['aberta', 'cancelada', 'finalizada'])->pluck('agendado_em')
            ->map(fn ($data) => CarbonImmutable::parse($data)->format('Y-m-d H:i:s'))->all();
        $bloqueios = OrdemServicoDisponibilidade::query()
            ->where('tecnico_id', $disponibilidade->tecnico_id)
            ->whereDate('data', $disponibilidade->data)
            ->where('tipo', OrdemServicoDisponibilidade::TIPO_BLOQUEIO)
            ->get(['hora_inicio', 'hora_fim']);

        $blocos = collect();
        while ($inicio->addMinutes(self::DURACAO_MINUTOS)->lessThanOrEqualTo($fim)) {
            $fimBloco = $inicio->addMinutes(self::DURACAO_MINUTOS);
            $bloqueado = $bloqueios->contains(fn (OrdemServicoDisponibilidade $bloqueio): bool => $inicio->format('H:i:s') < $bloqueio->hora_fim && $fimBloco->format('H:i:s') > $bloqueio->hora_inicio);
            if (! $bloqueado && ! in_array($inicio->format('Y-m-d H:i:s'), $ocupados, true)) {
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
