@php
    $veiculo->loadMissing(['cliente.estado', 'tecnicoInstala', 'contratos.tipoContrato', 'contratos.statusContrato']);
    $cliente = $veiculo->cliente;
    $tipos = \App\Models\TipoContrato::query()->where('is_active', true)->orderBy('order')->get();
    $contratos = $veiculo->contratos()->with(['tipoContrato', 'statusContrato'])->latest()->get();
    $documentosZapSign = [];
    $zapSignService = app(\App\Services\ZapSign\ZapSignService::class);

    foreach ($contratos as $contrato) {
        if (blank($contrato->doc_token)) {
            continue;
        }

        try {
            $documento = $zapSignService->detalhesDocumento((string) $contrato->doc_token);
            $documentosZapSign[$contrato->id] = [
                'original_file' => data_get($documento, 'original_file'),
                'signed_file' => data_get($documento, 'signed_file'),
                'signing_link' => data_get($documento, 'signers.0.signing_link') ?: data_get($documento, 'signers.0.sign_url'),
            ];
        } catch (\Throwable $exception) {
            $documentosZapSign[$contrato->id] = ['erro' => $exception->getMessage()];
        }
    }

    $cpfCnpj = $cliente?->cpf_cnpj_formatado ?? '';
    $telefoneNumeros = preg_replace('/\D+/', '', (string) $cliente?->telefone1);
    $telefone = strlen($telefoneNumeros) === 11 ? preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $telefoneNumeros) : (string) $cliente?->telefone1;
    $endereco = collect([$cliente?->rua, $cliente?->numero, $cliente?->setor])->filter()->implode('  ');
@endphp

<style>
    .ct-contract-modal { color: #111827; display: grid; gap: 12px; font-size: 13px; max-height: 72vh; overflow-y: auto; padding-right: 4px; }
    .ct-contract-modal h2 { font-size: 20px; font-weight: 800; margin: 0; }
    .ct-contract-modal details { border: 1px solid #d9dee7; border-radius: 8px; padding: 10px 12px; }
    .ct-contract-modal summary { color: #374151; cursor: pointer; font-size: 14px; font-weight: 800; }
    .ct-contract-modal details[open] summary { margin-bottom: 10px; }
    .ct-contract-modal .grid { display: grid; gap: 8px 12px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .ct-contract-modal .wide { grid-column: 1 / -1; }
    .ct-contract-modal .info { color: #374151; min-width: 0; overflow-wrap: anywhere; }
    .ct-contract-modal .info strong { color: #20242b; font-weight: 800; }
    .ct-contract-modal label { color: #4b5563; display: grid; gap: 3px; }
    .ct-contract-modal input { border: 1px solid #cbd5e1; border-radius: 6px; color: #111827; font-size: 13px; height: 34px; padding: 0 10px; width: 100%; }
    .ct-contract-modal .actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 10px; }
    .ct-contract-modal .btn { border: 0; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 800; height: 34px; padding: 0 14px; }
    .ct-contract-modal .btn-primary { background: #f59e0b; color: #111827; }
    .ct-contract-modal .btn-primary:disabled { cursor: wait; opacity: .65; }
    .ct-contract-modal .history { border-top: 1px solid #e5e7eb; padding-top: 10px; }
    .ct-contract-modal table { border-collapse: collapse; font-size: 12px; width: 100%; }
    .ct-contract-modal th, .ct-contract-modal td { border-bottom: 1px solid #e5e7eb; padding: 6px; text-align: left; }
    .ct-contract-modal .history-links { display: flex; flex-wrap: wrap; gap: 8px; }
    .ct-contract-modal .history-links a { color: #d97706; font-weight: 700; text-decoration: none; }
    .ct-contract-modal .history-links a:hover { text-decoration: underline; }
    .ct-contract-modal .history-error { color: #b91c1c; font-size: 11px; }
    .ct-contract-message { border-radius: 8px; display: none; font-size: 13px; font-weight: 700; padding: 10px 12px; }
    .ct-contract-message.is-error { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; display: block; }
    .ct-contract-message.is-success { background: #dcfce7; border: 1px solid #86efac; color: #166534; display: block; }
</style>

<div class="ct-contract-modal" x-data="{
    message: '',
    messageType: '',
    sending: false,
    async send(button) {
        const card = button.closest('[data-contract-card]');
        if (!card || this.sending) return;
        this.sending = true;
        this.message = '';
        this.messageType = '';
        const payload = new FormData();
        card.querySelectorAll('input[name]').forEach((input) => payload.append(input.name, input.value));
        try {
            const response = await fetch(card.dataset.url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: payload,
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                this.message = data.message || 'Nao foi possivel enviar o contrato.';
                this.messageType = 'error';
                return;
            }
            this.message = data.message || 'Documento enviado para a ZapSign.';
            this.messageType = 'success';
            window.setTimeout(() => window.location.reload(), 900);
        } catch (error) {
            this.message = 'Nao foi possivel conectar para enviar o contrato.';
            this.messageType = 'error';
        } finally {
            this.sending = false;
        }
    }
}">
    <h2>Dados do contrato</h2>

    <div class="ct-contract-message" x-show="message" x-text="message" :class="messageType === 'success' ? 'is-success' : 'is-error'"></div>

    @foreach ($tipos as $tipo)
        @php $isComodato = $tipo->label === 'Comodato'; @endphp
        <details @if ($loop->first) open @endif>
            <summary>{{ $tipo->label }}</summary>
            <div data-contract-card data-url="{{ route('contratos-rastreador.enviar', $veiculo) }}">
                <input type="hidden" name="tipo_contrato_id" value="{{ $tipo->id }}">

                @if (! $isComodato)
                    <div class="grid">
                        <div class="info wide"><strong>Contratante:</strong> {{ $cliente?->nome }}</div>
                        <div class="info"><strong>CPF/CNPJ:</strong> {{ $cpfCnpj }}</div>
                        <div class="info"><strong>Contato:</strong> {{ $telefone }}</div>
                        <div class="info wide"><strong>Endereco:</strong> {{ $endereco }}</div>
                        <div class="info"><strong>Cep:</strong> {{ $cliente?->cep }}</div>
                        <div class="info"><strong>Cidade:</strong> {{ $cliente?->cidade }}</div>
                        <div class="info"><strong>UF:</strong> {{ $cliente?->estado?->label }}</div>
                        <div class="info"><strong>Email:</strong> {{ $cliente?->email }}</div>
                        <div class="info"><strong>Veiculo:</strong> {{ $veiculo->veiculo }}</div>
                        <div class="info"><strong>Placa:</strong> {{ $veiculo->placa }}</div>
                        <div class="info"><strong>Instalacao:</strong> {{ $veiculo->data_instalacao?->format('Y-m-d') }}</div>
                        <div class="info"><strong>Vencimento:</strong> {{ $cliente?->dia_pagamento }}</div>
                        <label class="wide">Valor mensal<input type="text" name="valor_mensal"></label>
                    </div>
                @else
                    <div class="grid">
                        <label>Contratante<input type="text" name="comodato_contratante"></label>
                        <label>CPF<input type="text" name="comodato_cpf"></label>
                        <label>Email<input type="text" name="comodato_email"></label>
                        <label>Telefone<input type="text" name="comodato_telefone"></label>
                        <label>Veiculo<input type="text" name="comodato_veiculo" value="{{ $veiculo->veiculo }}"></label>
                        <label>Placa<input type="text" name="comodato_placa" value="{{ $veiculo->placa }}"></label>
                        <label>Data instalacao<input type="date" name="comodato_data_instalacao"></label>
                        <label>Tecnico<input type="text" name="comodato_tecnico" value="{{ $veiculo->tecnicoInstala?->nome }}"></label>
                        <label>Empresa<input type="text" name="comodato_empresa" value="{{ $cliente?->nome }}"></label>
                        <label>CNPJ<input type="text" name="comodato_cnpj" value="{{ $cpfCnpj }}"></label>
                    </div>
                @endif

                <div class="actions">
                    <button class="btn btn-primary" type="button" data-contract-send @click="send($el)" x-text="sending ? 'Enviando...' : 'Enviar'" :disabled="sending">Enviar</button>
                </div>
            </div>
        </details>
    @endforeach

    <div class="history">
        <strong>Historico</strong>
        <table>
            <thead><tr><th>Data</th><th>Tipo</th><th>Status</th><th>Documentos</th></tr></thead>
            <tbody>
                @forelse ($contratos as $contrato)
                    @php $linksDocumento = $documentosZapSign[$contrato->id] ?? []; @endphp
                    <tr>
                        <td>{{ $contrato->created_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ $contrato->tipoContrato?->label }}</td>
                        <td>{{ $contrato->statusContrato?->label }}</td>
                        <td>
                            @if (blank($contrato->doc_token))
                                -
                            @elseif (! empty($linksDocumento['erro']))
                                <span class="history-error">{{ $linksDocumento['erro'] }}</span>
                            @else
                                <span class="history-links">
                                    @if (! empty($linksDocumento['original_file']))
                                        <a href="{{ $linksDocumento['original_file'] }}" target="_blank" rel="noopener noreferrer">Sem assinar</a>
                                    @endif
                                    @if (! empty($linksDocumento['signed_file']))
                                        <a href="{{ $linksDocumento['signed_file'] }}" target="_blank" rel="noopener noreferrer">Assinado</a>
                                    @endif
                                    @if (empty($linksDocumento['original_file']) && empty($linksDocumento['signed_file']) && ! empty($linksDocumento['signing_link']))
                                        <a href="{{ $linksDocumento['signing_link'] }}" target="_blank" rel="noopener noreferrer">Assinar</a>
                                    @endif
                                    @if (empty($linksDocumento['original_file']) && empty($linksDocumento['signed_file']) && empty($linksDocumento['signing_link']))
                                        -
                                    @endif
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4">Nenhum contrato registrado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

