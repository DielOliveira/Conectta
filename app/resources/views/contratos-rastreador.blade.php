@php
    $tipoSelecionado = $tipos->firstWhere('id', $tipoSelecionadoId) ?? $tipos->first();
    $isComodato = $tipoSelecionado?->label === 'Comodato';
    $contratoAtual = $contratos->where('tipo_contrato_id', $tipoSelecionado?->id)->sortByDesc('created_at')->first();
    $podeEnviarContrato = ($contratoAtual?->statusContrato?->label ?? 'Nao Enviado') === 'Nao Enviado';
    $cliente = $veiculo->cliente;
    $cpfCnpj = $cliente?->cpf_cnpj_formatado ?? '';
    $telefoneNumeros = preg_replace('/\D+/', '', (string) $cliente?->telefone1);
    $telefone = strlen($telefoneNumeros) === 11 ? preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $telefoneNumeros) : (string) $cliente?->telefone1;
    $endereco = collect([$cliente?->rua, $cliente?->numero, $cliente?->setor])->filter()->implode('  ');
@endphp
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contratos</title>
    <style>
        :root { --ct-primary: #f59e0b; --ct-primary-strong: #d97706; --ct-primary-soft: rgba(245, 158, 11, .18); }
        body { background: #f3f6f9; color: #111827; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 0; }
        .topbar { align-items: center; background: #fff; border-bottom: 1px solid #e5e7eb; display: flex; height: 64px; justify-content: space-between; padding: 0 28px; }
        .brand { font-size: 22px; font-weight: 800; }
        .topbar a { color: #374151; font-weight: 700; text-decoration: none; }
        main { display: grid; gap: 18px; max-width: 820px; padding: 32px; }
        .card { background: #fff; border: 1px solid #d9dee7; border-radius: 8px; padding: 24px; }
        h1 { font-size: 28px; margin: 0 0 8px; }
        h2 { font-size: 22px; margin: 0 0 16px; }
        .status { font-size: 16px; font-weight: 800; margin-bottom: 24px; }
        .success { background: #dcfce7; border: 1px solid #86efac; border-radius: 8px; color: #166534; font-weight: 700; padding: 12px 14px; }
        .types { display: grid; gap: 16px; grid-template-columns: repeat(3, minmax(0, 1fr)); margin-bottom: 26px; }
        .type { align-items: center; color: #4b5563; display: flex; font-size: 17px; gap: 10px; }
        .type input { accent-color: var(--ct-primary); height: 28px; width: 28px; }
        .grid { display: grid; gap: 14px 20px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .wide { grid-column: 1 / -1; }
        .info { color: #374151; font-size: 16px; min-width: 0; overflow-wrap: anywhere; }
        .info strong { color: #20242b; font-weight: 800; }
        label { color: #4b5563; display: grid; font-size: 15px; gap: 5px; }
        input[type="text"], input[type="date"] { border: 1px solid #cbd5e1; border-radius: 6px; color: #111827; font-size: 16px; height: 48px; outline: none; padding: 0 14px; width: 100%; }
        input:focus { border-color: var(--ct-primary); box-shadow: 0 0 0 3px var(--ct-primary-soft); }
        .actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 28px; }
        .btn { align-items: center; border-radius: 6px; display: inline-flex; font-weight: 800; height: 44px; justify-content: center; padding: 0 22px; text-decoration: none; }
        .btn-primary { background: var(--ct-primary); border: 0; color: #111827; cursor: pointer; }
        .btn-primary:hover { background: var(--ct-primary-strong); }
        .btn-secondary { background: #fff; border: 1px solid #d1d5db; color: #111827; }
        table { border-collapse: collapse; font-size: 14px; width: 100%; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 12px 10px; text-align: left; }
        th { color: #374151; font-weight: 800; }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="brand">Conectta</div>
        <a href="/admin/rastreadores/{{ $veiculo->id }}/edit">Voltar ao rastreador</a>
    </header>

    <main>
        @if (session('status'))
            <div class="success">{{ session('status') }}</div>
        @endif

        <section class="card">
            <h1>Dados do contrato</h1>
            <div class="status">Status: {{ $contratoAtual?->statusContrato?->label ?? 'Nao Enviado' }}</div>

            <form method="post" action="{{ route('contratos-rastreador.enviar', $veiculo) }}">
                @csrf

                <div class="types">
                    @foreach ($tipos as $tipo)
                        <label class="type">
                            <input type="radio" name="tipo_contrato_id" value="{{ $tipo->id }}" @checked($tipo->id === $tipoSelecionado?->id) onchange="window.location='{{ route('contratos-rastreador.show', $veiculo) }}?tipo_contrato_id={{ $tipo->id }}'">
                            <span>{{ $tipo->label }}</span>
                        </label>
                    @endforeach
                </div>

                @if (! $isComodato)
                    <div class="grid">
                        <div class="info wide"><strong>Contratante:</strong> {{ $cliente?->nome }}</div>
                        <div class="info"><strong>CPF / CNPJ:</strong> {{ $cpfCnpj }}</div>
                        <div class="info"><strong>Contato:</strong> {{ $telefone }}</div>
                        <div class="info wide"><strong>Endereco:</strong> {{ $endereco }}</div>
                        <div class="info"><strong>Cep:</strong> {{ $cliente?->cep }}</div>
                        <div class="info"><strong>Cidade:</strong> {{ $cliente?->cidade }}</div>
                        <div class="info"><strong>UF:</strong> {{ $cliente?->estado?->label }}</div>
                        <div class="info"><strong>Email:</strong> {{ $cliente?->email }}</div>
                        <div class="info"><strong>Veiculo:</strong> {{ $veiculo->veiculo }}</div>
                        <div class="info"><strong>Placa:</strong> {{ $veiculo->placa }}</div>
                        <div class="info"><strong>Data de Instalacao:</strong> {{ $veiculo->data_instalacao?->format('Y-m-d') }}</div>
                        <div class="info"><strong>Valor de Instalacao:</strong> {{ number_format((float) ($veiculo->valor_instalacao ?? 0), 2, ',', '.') }}</div>
                        <div class="info"><strong>Valor Mensal:</strong> 0,00</div>
                        <div class="info"><strong>Data de Vencimento:</strong> {{ $cliente?->dia_pagamento }}</div>
                        <label>Valor mensal<input type="text" name="valor_mensal"></label>
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
                    <a class="btn btn-secondary" href="/admin/rastreadores/{{ $veiculo->id }}/edit">Cancelar</a>
                    @if ($podeEnviarContrato)
                        <button class="btn btn-primary" type="submit">Enviar</button>
                    @endif
                </div>
            </form>
        </section>

        <section class="card">
            <h2>Historico</h2>
            <table>
                <thead><tr><th>Data</th><th>Tipo</th><th>Status</th><th>Token</th></tr></thead>
                <tbody>
                    @forelse ($contratos as $contrato)
                        <tr>
                            <td>{{ $contrato->created_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ $contrato->tipoContrato?->label }}</td>
                            <td>{{ $contrato->statusContrato?->label }}</td>
                            <td>{{ $contrato->doc_token ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">Nenhum contrato registrado para este rastreador.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
