<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\Permission;
use App\Models\StatusContrato;
use App\Models\TipoContrato;
use App\Models\Veiculo;
use App\Services\ZapSign\ZapSignException;
use App\Services\ZapSign\ZapSignService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContratosRastreadorController extends Controller
{
    public function show(Veiculo $veiculo): View|RedirectResponse
    {
        if (! $this->podeAcessar()) {
            abort(403, 'Voce nao tem permissao para acessar contratos.');
        }

        $veiculo->load(['cliente.estado', 'tipoVeiculo', 'tecnicoInstala', 'contratos.tipoContrato', 'contratos.statusContrato']);

        return view('contratos-rastreador', [
            'veiculo' => $veiculo,
            'tipos' => TipoContrato::query()->where('is_active', true)->orderBy('order')->get(),
            'tipoSelecionadoId' => (int) request()->integer('tipo_contrato_id', TipoContrato::query()->where('label', 'Principal')->value('id')),
            'contratos' => $veiculo->contratos()->with(['tipoContrato', 'statusContrato'])->latest()->get(),
        ]);
    }

    public function enviar(Request $request, Veiculo $veiculo, ZapSignService $zapSignService): RedirectResponse|JsonResponse
    {
        if (! auth()->user()?->hasPermission(Permission::CADASTRO_ESCRITA)) {
            abort(403, 'Voce nao tem permissao para esta acao.');
        }

        $data = $request->validate(
            ['tipo_contrato_id' => ['required', 'exists:tipo_contratos,id']],
            [
                'required' => 'O campo :attribute e obrigatorio.',
                'exists' => 'O campo :attribute e invalido.',
            ],
            ['tipo_contrato_id' => 'tipo de contrato'],
        );

        $tipoContrato = TipoContrato::query()->findOrFail($data['tipo_contrato_id']);
        $contrato = $veiculo->contratos()
            ->with('statusContrato')
            ->where('tipo_contrato_id', $tipoContrato->id)
            ->latest()
            ->first();

        if (($contrato?->statusContrato?->label ?? 'Nao Enviado') !== 'Nao Enviado') {
            $message = 'Apenas contratos com status Nao Enviado podem ser enviados.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['contrato' => $message]);
        }

        try {
            $response = $zapSignService->criarDocumento($veiculo, $tipoContrato, $request->all());
        } catch (ZapSignException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['zapsign' => $exception->getMessage()]);
        }

        ($contrato ?: new Contrato)->forceFill([
            'veiculo_id' => $veiculo->id,
            'tipo_contrato_id' => $tipoContrato->id,
            'status_contrato_id' => StatusContrato::query()->where('label', 'Enviado')->value('id'),
            'doc_token' => data_get($response, 'token'),
        ])->save();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Documento enviado para a ZapSign.']);
        }

        return redirect()
            ->route('contratos-rastreador.show', ['veiculo' => $veiculo->id, 'tipo_contrato_id' => $tipoContrato->id])
            ->with('status', 'Documento enviado para a ZapSign.');
    }

    private function podeAcessar(): bool
    {
        $user = auth()->user();

        return ($user?->isAdmin() ?? false)
            || ($user?->hasPermission(Permission::CADASTRO_LEITURA) ?? false)
            || ($user?->hasPermission(Permission::CADASTRO_ESCRITA) ?? false);
    }
}
