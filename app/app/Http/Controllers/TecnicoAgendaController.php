<?php

namespace App\Http\Controllers;

use App\Models\OrdemServicoDisponibilidade;
use App\Services\OrdemServico\OrdemServicoAgendaService;
use App\Services\OrdemServico\TecnicoAgendaPublicaService;
use Illuminate\Http\Request;

class TecnicoAgendaController extends Controller
{
    public function show(string $token, TecnicoAgendaPublicaService $publica)
    {
        $tecnico = $publica->tecnicoPorToken($token);
        $disponibilidades = $tecnico->disponibilidadesOrdemServico()->withCount('ordens')
            ->whereDate('data', '>=', today())->orderBy('data')->orderBy('hora_inicio')->get();

        return view('ordens-servico.agenda-tecnico', compact('tecnico', 'token', 'disponibilidades'));
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
