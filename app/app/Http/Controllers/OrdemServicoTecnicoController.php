<?php

namespace App\Http\Controllers;

use App\Enums\OrdemServicoStatus;
use App\Enums\OrdemServicoTipo;
use App\Models\Chip;
use App\Models\OrdemServico;
use App\Models\OrdemServicoFoto;
use App\Models\Rastreador;
use App\Services\OrdemServico\OrdemServicoEquipamentoReserva;
use App\Services\OrdemServico\OrdemServicoFotoStorage;
use App\Services\OrdemServico\OrdemServicoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrdemServicoTecnicoController extends Controller
{
    public function show(string $token, OrdemServicoService $service)
    {
        $ordem = $service->porToken($token)->load(['cliente', 'veiculo.rastreador.chip', 'tecnico', 'fotos', 'historicos']);
        $rastreadores = collect();
        $chips = collect();
        if ($ordem->tecnico_id && in_array($ordem->status, [OrdemServicoStatus::EM_ATENDIMENTO, OrdemServicoStatus::PENDENTE], true)) {
            $rastreadores = Rastreador::query()
                ->with('chip')
                ->where('tecnico_id', $ordem->tecnico_id)
                ->whereHas('statusRastreador', fn ($query) => $query->where('label', 'Disponivel'))
                ->where(fn ($query) => $query
                    ->whereNull('chip_id')
                    ->orWhereHas('chip.statusRastreador', fn ($chipQuery) => $chipQuery->where('label', 'Disponivel')))
                ->tap(fn ($query) => OrdemServicoEquipamentoReserva::excluirRastreadoresReservados($query, $ordem->id))
                ->orderBy('imei')
                ->get();
            $chips = Chip::query()
                ->where('tecnico_id', $ordem->tecnico_id)
                ->whereHas('statusRastreador', fn ($query) => $query->where('label', 'Disponivel'))
                ->whereDoesntHave('rastreador')
                ->tap(fn ($query) => OrdemServicoEquipamentoReserva::excluirChipsReservados($query, $ordem->id))
                ->orderBy('numero_chip')
                ->get();
        }

        return view('ordens-servico.tecnico', compact('ordem', 'token', 'rastreadores', 'chips'));
    }

    public function action(Request $request, string $token, OrdemServicoService $service, OrdemServicoFotoStorage $fotoStorage)
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
            $this->armazenarFotos($ordem, $request->file('fotos', []), $fotoStorage);
            $ordem->update(['status' => OrdemServicoStatus::AGUARDANDO_CORRECAO_CADASTRAL]);
            $ordem->historicos()->create(['evento' => 'divergencia_cadastral', 'status_anterior' => $anterior->value, 'status_novo' => OrdemServicoStatus::AGUARDANDO_CORRECAO_CADASTRAL->value, 'tecnico_id' => $ordem->tecnico_id, 'observacao' => $request->string('observacao')]);
        } elseif ($acao === 'conferencia') {
            if (is_string($request->input('local_instalacao'))) {
                $request->merge(['local_instalacao' => trim($request->input('local_instalacao'))]);
            }
            $request->validate(
                [
                    'fotos' => ['array', 'max:4'], 'fotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
                    'rastreador_novo_id' => ['nullable', 'integer'], 'chip_novo_id' => ['nullable', 'integer'],
                    'resultado_manutencao' => ['nullable', Rule::in(['reparo_sem_troca', 'troca_rastreador', 'troca_chip', 'troca_rastreador_chip', 'sem_defeito'])],
                    'descricao_atendimento' => ['nullable', 'string', 'max:5000'], 'equipamentos_confirmados' => ['nullable', 'boolean'],
                    'bloqueio' => [
                        Rule::requiredIf(in_array($ordem->tipo, [OrdemServicoTipo::INSTALACAO, OrdemServicoTipo::MANUTENCAO], true)),
                        'nullable', 'boolean',
                    ],
                    'local_instalacao' => [
                        Rule::requiredIf(in_array($ordem->tipo, [OrdemServicoTipo::INSTALACAO, OrdemServicoTipo::MANUTENCAO], true)),
                        'nullable', 'string', 'max:500',
                    ],
                ],
                $this->mensagensValidacaoFotos(),
            );
            if ($ordem->fotos()->count() + count($request->file('fotos', [])) > 4) {
                return back()->withErrors(['fotos' => 'A OS aceita no máximo quatro fotos.']);
            }
            $this->armazenarFotos($ordem, $request->file('fotos', []), $fotoStorage);
            DB::transaction(function () use ($ordem, $request, $service): void {
                $ordem = OrdemServico::query()->lockForUpdate()->findOrFail($ordem->id);
                abort_unless(in_array($ordem->status, [OrdemServicoStatus::EM_ATENDIMENTO, OrdemServicoStatus::PENDENTE], true), 409);

                $rastreadorId = $request->integer('rastreador_novo_id') ?: null;
                $chipId = $request->integer('chip_novo_id') ?: null;
                $rastreador = $rastreadorId ? Rastreador::query()
                    ->whereKey($rastreadorId)
                    ->where('tecnico_id', $ordem->tecnico_id)
                    ->whereHas('statusRastreador', fn ($query) => $query->where('label', 'Disponivel'))
                    ->lockForUpdate()
                    ->first() : null;
                if ($rastreadorId && ! $rastreador) {
                    abort(422, 'Rastreador indisponível para este técnico.');
                }
                if ($rastreadorId && $mensagem = OrdemServicoEquipamentoReserva::mensagemRastreador($rastreadorId, $ordem->id)) {
                    throw ValidationException::withMessages(['rastreador_novo_id' => $mensagem]);
                }
                if ($ordem->tipo === OrdemServicoTipo::INSTALACAO && ! $rastreador) {
                    throw ValidationException::withMessages(['rastreador_novo_id' => 'Selecione o rastreador que será instalado.']);
                }
                if ($rastreador?->chip_id) {
                    $chipId = $rastreador->chip_id;
                }
                $chip = $chipId ? Chip::query()->lockForUpdate()->find($chipId) : null;
                $chipValido = ! $chipId
                    || ($rastreador?->chip_id === $chipId)
                    || ($chip
                        && (int) $chip->tecnico_id === (int) $ordem->tecnico_id
                        && $chip->statusRastreador?->label === 'Disponivel'
                        && $chip->rastreador()->doesntExist());
                if (! $chipValido) {
                    abort(422, 'Chip indisponível para este técnico.');
                }
                if ($chipId && $mensagem = OrdemServicoEquipamentoReserva::mensagemChip($chipId, $ordem->id)) {
                    throw ValidationException::withMessages(['chip_novo_id' => $mensagem]);
                }
                if ($ordem->tipo === OrdemServicoTipo::INSTALACAO && ! $chipId) {
                    throw ValidationException::withMessages(['chip_novo_id' => 'Selecione um chip livre para o rastreador.']);
                }
                $ordem->update([
                    'rastreador_novo_id' => $rastreadorId,
                    'chip_novo_id' => $chipId,
                    'resultado_manutencao' => $request->input('resultado_manutencao'),
                    'descricao_atendimento' => $request->input('descricao_atendimento'),
                    'local_instalacao' => $request->input('local_instalacao'),
                    'bloqueio' => in_array($ordem->tipo, [OrdemServicoTipo::INSTALACAO, OrdemServicoTipo::MANUTENCAO], true)
                        ? $request->boolean('bloqueio')
                        : null,
                    'equipamentos_confirmados' => $request->boolean('equipamentos_confirmados'),
                ]);
                $service->solicitarConferencia($ordem->fresh());
            });
        } elseif ($acao === 'remover_foto') {
            abort_unless(in_array($ordem->status, [OrdemServicoStatus::EM_ATENDIMENTO, OrdemServicoStatus::PENDENTE], true), 409);
            $foto = $ordem->fotos()->findOrFail($request->integer('foto_id'));
            $fotoStorage->excluir($foto->caminho);
            $foto->delete();
        } else {
            abort(400);
        }

        return redirect()->route('ordens-servico.tecnico', ['token' => $token])->with('status', 'Ação registrada com sucesso.');
    }

    public function foto(string $token, OrdemServicoFoto $foto, OrdemServicoService $service, OrdemServicoFotoStorage $fotoStorage)
    {
        $ordem = $service->porToken($token);
        abort_unless((int) $foto->ordem_servico_id === (int) $ordem->id, 404);

        return $fotoStorage->resposta($foto->caminho, $foto->nome_original, $foto->mime_type);
    }

    private function armazenarFotos($ordem, array $fotos, OrdemServicoFotoStorage $fotoStorage): void
    {
        foreach ($fotos as $foto) {
            $caminho = $fotoStorage->armazenar($foto, $ordem->id);
            try {
                $ordem->fotos()->create(['caminho' => $caminho, 'nome_original' => $foto->getClientOriginalName(), 'mime_type' => $foto->getMimeType(), 'tamanho' => $foto->getSize()]);
            } catch (\Throwable $exception) {
                $fotoStorage->excluir($caminho);
                throw $exception;
            }
        }
    }

    private function mensagensValidacaoFotos(): array
    {
        return [
            'local_instalacao.required' => 'Informe o local de instalação.',
            'local_instalacao.max' => 'O local de instalação deve ter no máximo 500 caracteres.',
            'bloqueio.required' => 'Informe se há bloqueio.',
            'bloqueio.boolean' => 'Selecione Sim ou Não no campo Bloqueio.',
            'fotos.max' => 'A OS aceita no máximo quatro fotos.',
            'fotos.*.uploaded' => 'Não foi possível enviar uma das fotos. Use um arquivo de até 5 MB.',
            'fotos.*.image' => 'Envie somente arquivos de imagem.',
            'fotos.*.mimes' => 'As fotos devem estar nos formatos JPG, PNG ou WEBP.',
            'fotos.*.max' => 'Cada foto deve ter no máximo 5 MB.',
        ];
    }
}
