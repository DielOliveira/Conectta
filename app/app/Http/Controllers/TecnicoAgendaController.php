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
        $semanaInformada = $request->string('semana')->toString();
        $referencia = preg_match('/^\d{4}-\d{2}-\d{2}$/', $semanaInformada) === 1
            ? CarbonImmutable::parse($semanaInformada)
            : CarbonImmutable::today();
        $inicioSemana = $referencia->startOfWeek(CarbonInterface::MONDAY);
        $fimSemana = $inicioSemana->addDays(6)->endOfDay();
        $semanaAtual = $inicioSemana->isSameDay(CarbonImmutable::today()->startOfWeek(CarbonInterface::MONDAY));
        $modoAgenda = $request->string('modo_agenda')->toString() === 'semana' ? 'semana' : 'dia';
        $disponibilidades = $tecnico->disponibilidadesOrdemServico()->withCount('ordens')
            ->whereDate('data', '>=', $inicioSemana->toDateString())
            ->whereDate('data', '<=', $fimSemana->toDateString())
            ->orderBy('data')->orderBy('hora_inicio')->get();
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
            'modoAgenda',
        ));
    }

    public function store(Request $request, string $token, TecnicoAgendaPublicaService $publica, OrdemServicoAgendaService $agenda)
    {
        $tecnico = $publica->tecnicoPorToken($token);
        $request->merge([
            'modo' => $request->input('modo', 'dia'),
            'tipo' => $request->input('tipo', OrdemServicoDisponibilidade::TIPO_DISPONIBILIDADE),
        ]);
        $dados = $request->validate([
            'modo' => ['required', 'in:dia,semana'],
            'tipo' => ['required', 'in:disponibilidade,bloqueio'],
            'data' => ['required', 'date'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fim' => ['required', 'date_format:H:i'],
        ]);
        if ($dados['modo'] === 'semana') {
            $agenda->criarSemana($tecnico->id, $dados['data'], $dados['hora_inicio'], $dados['hora_fim'], $dados['tipo']);
        } else {
            $agenda->criarIntervalo($tecnico->id, $dados['data'], $dados['hora_inicio'], $dados['hora_fim'], $dados['tipo']);
        }

        $mensagem = $dados['tipo'] === OrdemServicoDisponibilidade::TIPO_BLOQUEIO ? 'Bloqueio incluído na sua agenda.' : 'Horário adicionado à sua agenda.';
        $destino = $dados['modo'] === 'semana'
            ? route('tecnicos.agenda', ['token' => $token, 'modo_agenda' => 'semana', 'semana' => CarbonImmutable::parse($dados['data'])->startOfWeek(CarbonInterface::MONDAY)->toDateString()])
            : route('tecnicos.agenda', $token);

        return redirect($destino)->with('status', $mensagem);
    }

    public function destroy(string $token, OrdemServicoDisponibilidade $disponibilidade, TecnicoAgendaPublicaService $publica)
    {
        $tecnico = $publica->tecnicoPorToken($token);
        $publica->excluir($disponibilidade, $tecnico);

        return redirect()->route('tecnicos.agenda', $token)->with('status', 'Horário removido da sua agenda.');
    }
}
