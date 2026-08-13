<?php

namespace App\Http\Controllers;

use App\Models\OrdemServicoDisponibilidade;
use App\Services\OrdemServico\OrdemServicoAgendaService;
use App\Services\OrdemServico\TecnicoAgendaPublicaService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;

class TecnicoAgendaController extends Controller
{
    public function show(Request $request, string $token, TecnicoAgendaPublicaService $publica)
    {
        $tecnico = $publica->tecnicoPorToken($token);
        $disponibilidades = $tecnico->disponibilidadesOrdemServico()->withCount('ordens')
            ->whereDate('data', '>=', today())->orderBy('data')->orderBy('hora_inicio')->get();
        $semanaInformada = $request->string('semana')->toString();
        $referencia = preg_match('/^\d{4}-\d{2}-\d{2}$/', $semanaInformada) === 1
            ? CarbonImmutable::parse($semanaInformada)
            : CarbonImmutable::today();
        $inicioSemana = $referencia->startOfWeek(CarbonInterface::MONDAY);
        $fimSemana = $inicioSemana->addDays(6)->endOfDay();
        $semanaAtual = $inicioSemana->isSameDay(CarbonImmutable::today()->startOfWeek(CarbonInterface::MONDAY));
        $ordens = $tecnico->ordensServico()
            ->with(['cliente', 'veiculo'])
            ->whereBetween('agendado_em', [$inicioSemana->startOfDay(), $fimSemana->endOfDay()])
            ->orderBy('agendado_em')
            ->orderBy('numero')
            ->get();

        return view('ordens-servico.agenda-tecnico', compact(
            'tecnico',
            'token',
            'disponibilidades',
            'ordens',
            'inicioSemana',
            'fimSemana',
            'semanaAtual',
        ));
    }

    public function store(Request $request, string $token, TecnicoAgendaPublicaService $publica, OrdemServicoAgendaService $agenda)
    {
        $tecnico = $publica->tecnicoPorToken($token);
        $dados = $request->validate([
            'data' => ['required', 'date', 'after_or_equal:today'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fim' => ['required', 'date_format:H:i'],
        ]);
        $agenda->criarDisponibilidade($tecnico->id, $dados['data'], $dados['hora_inicio'], $dados['hora_fim']);

        return redirect()->route('tecnicos.agenda', $token)->with('status', 'Horário adicionado à sua agenda.');
    }

    public function destroy(string $token, OrdemServicoDisponibilidade $disponibilidade, TecnicoAgendaPublicaService $publica)
    {
        $tecnico = $publica->tecnicoPorToken($token);
        $publica->excluir($disponibilidade, $tecnico);

        return redirect()->route('tecnicos.agenda', $token)->with('status', 'Horário removido da sua agenda.');
    }
}
