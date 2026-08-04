<?php

namespace App\Http\Controllers;

use App\Enums\OrdemServicoStatus;
use App\Enums\OrdemServicoTipo;
use App\Models\Chip;
use App\Models\OrdemServicoFoto;
use App\Models\Rastreador;
use App\Services\OrdemServico\OrdemServicoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class OrdemServicoTecnicoController extends Controller
{
    public function show(string $token, OrdemServicoService $service)
    {
        $ordem = $service->porToken($token)->load(['cliente', 'veiculo.rastreador.chip', 'tecnico', 'fotos', 'historicos']);
        $rastreadores = collect();
        $chips = collect();
        if ($ordem->tecnico_id && in_array($ordem->status, [OrdemServicoStatus::EM_ATENDIMENTO, OrdemServicoStatus::PENDENTE], true)) {
            $rastreadores = Rastreador::query()->with('chip')->where('tecnico_id', $ordem->tecnico_id)->where('is_estoque', true)->orderBy('imei')->get();
            $chips = Chip::query()->where('tecnico_id', $ordem->tecnico_id)->whereDoesntHave('rastreador')->orderBy('numero_chip')->get();
        }

        return view('ordens-servico.tecnico', compact('ordem', 'token', 'rastreadores', 'chips'));
    }

    public function action(Request $request, string $token, OrdemServicoService $service)
    {
        $ordem = $service->porToken($token);
        $acao = $request->string('acao')->toString();
        if ($acao === 'aceitar') {
            $service->aceitar($ordem);
        } elseif ($acao === 'rejeitar') {
            $request->validate(['motivo' => ['required', 'string', 'max:2000']]);
            $service->rejeitar($ordem, $request->string('motivo'));

            return redirect()->route('ordens-servico.tecnico.rejeicao-confirmada');
        } elseif ($acao === 'iniciar') {
            $service->iniciar($ordem, $request->float('latitude') ?: null, $request->float('longitude') ?: null);
        } elseif ($acao === 'divergencia') {
            $request->validate(
                ['observacao' => ['required', 'string', 'max:3000'], 'fotos' => ['array', 'max:4'], 'fotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120']],
                $this->mensagensValidacaoFotos(),
            );
            $anterior = $ordem->status;
            abort_unless($anterior === OrdemServicoStatus::EM_ATENDIMENTO, 409);
            if ($ordem->fotos()->count() + count($request->file('fotos', [])) > 4) {
                return back()->withErrors(['fotos' => 'A OS aceita no máximo quatro fotos.']);
            }
            $this->armazenarFotos($ordem, $request->file('fotos', []));
            $ordem->update(['status' => OrdemServicoStatus::AGUARDANDO_CORRECAO_CADASTRAL]);
            $ordem->historicos()->create(['evento' => 'divergencia_cadastral', 'status_anterior' => $anterior->value, 'status_novo' => OrdemServicoStatus::AGUARDANDO_CORRECAO_CADASTRAL->value, 'tecnico_id' => $ordem->tecnico_id, 'observacao' => $request->string('observacao')]);
        } elseif ($acao === 'conferencia') {
            $request->validate(
                [
                    'fotos' => ['array', 'max:4'], 'fotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
                    'rastreador_novo_id' => ['nullable', 'integer'], 'chip_novo_id' => ['nullable', 'integer'],
                    'resultado_manutencao' => ['nullable', Rule::in(['reparo_sem_troca', 'troca_rastreador', 'troca_chip', 'troca_rastreador_chip', 'sem_defeito'])],
                    'descricao_atendimento' => ['nullable', 'string', 'max:5000'], 'equipamentos_confirmados' => ['nullable', 'boolean'],
                ],
                $this->mensagensValidacaoFotos(),
            );
            if ($ordem->fotos()->count() + count($request->file('fotos', [])) > 4) {
                return back()->withErrors(['fotos' => 'A OS aceita no máximo quatro fotos.']);
            }
            $rastreadorId = $request->integer('rastreador_novo_id') ?: null;
            $chipId = $request->integer('chip_novo_id') ?: null;
            $rastreador = $rastreadorId ? Rastreador::query()->whereKey($rastreadorId)->where('tecnico_id', $ordem->tecnico_id)->where('is_estoque', true)->first() : null;
            if ($rastreadorId && ! $rastreador) {
                abort(422, 'Rastreador fora do estoque do técnico.');
            }
            if ($ordem->tipo === OrdemServicoTipo::INSTALACAO && ! $rastreador) {
                return back()->withErrors(['rastreador_novo_id' => 'Selecione o rastreador que será instalado.'])->withInput();
            }
            if ($rastreador?->chip_id) {
                $chipId = $rastreador->chip_id;
            }
            $chipValido = ! $chipId
                || ($rastreador?->chip_id === $chipId)
                || Chip::query()->whereKey($chipId)->where('tecnico_id', $ordem->tecnico_id)->whereDoesntHave('rastreador')->exists();
            if (! $chipValido) {
                abort(422, 'Chip fora do estoque do técnico.');
            }
            if ($ordem->tipo === OrdemServicoTipo::INSTALACAO && ! $chipId) {
                return back()->withErrors(['chip_novo_id' => 'Selecione um chip livre para o rastreador.'])->withInput();
            }
            $ordem->update(['rastreador_novo_id' => $rastreadorId, 'chip_novo_id' => $chipId,
                'resultado_manutencao' => $request->input('resultado_manutencao'), 'descricao_atendimento' => $request->input('descricao_atendimento'),
                'equipamentos_confirmados' => $request->boolean('equipamentos_confirmados')]);
            $this->armazenarFotos($ordem, $request->file('fotos', []));
            $service->solicitarConferencia($ordem->fresh());
        } elseif ($acao === 'remover_foto') {
            abort_unless(in_array($ordem->status, [OrdemServicoStatus::EM_ATENDIMENTO, OrdemServicoStatus::PENDENTE], true), 409);
            $foto = $ordem->fotos()->findOrFail($request->integer('foto_id'));
            Storage::disk('local')->delete($foto->caminho);
            $foto->delete();
        } else {
            abort(400);
        }

        return redirect()->route('ordens-servico.tecnico', ['token' => $token])->with('status', 'Ação registrada com sucesso.');
    }

    public function foto(string $token, OrdemServicoFoto $foto, OrdemServicoService $service)
    {
        $ordem = $service->porToken($token);
        abort_unless((int) $foto->ordem_servico_id === (int) $ordem->id, 404);

        return Storage::disk('local')->response($foto->caminho, $foto->nome_original, ['Content-Type' => $foto->mime_type]);
    }

    private function armazenarFotos($ordem, array $fotos): void
    {
        foreach ($fotos as $foto) {
            $caminho = $foto->store('ordens-servico/'.$ordem->id, 'local');
            $ordem->fotos()->create(['caminho' => $caminho, 'nome_original' => $foto->getClientOriginalName(), 'mime_type' => $foto->getMimeType(), 'tamanho' => $foto->getSize()]);
        }
    }

    private function mensagensValidacaoFotos(): array
    {
        return [
            'fotos.max' => 'A OS aceita no máximo quatro fotos.',
            'fotos.*.uploaded' => 'Não foi possível enviar uma das fotos. Use um arquivo de até 5 MB.',
            'fotos.*.image' => 'Envie somente arquivos de imagem.',
            'fotos.*.mimes' => 'As fotos devem estar nos formatos JPG, PNG ou WEBP.',
            'fotos.*.max' => 'Cada foto deve ter no máximo 5 MB.',
        ];
    }
}
