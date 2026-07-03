<x-filament-panels::page>
    <style>
        .ct-routine-page {
            display: grid;
            gap: 16px;
        }

        .ct-routine-summary {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(7, minmax(120px, 1fr));
            padding: 16px;
        }

        .ct-routine-stat {
            display: grid;
            gap: 4px;
        }

        .ct-routine-label {
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .ct-routine-value {
            color: #0f172a;
            font-size: 15px;
            font-weight: 800;
        }

        .ct-routine-table-wrap {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow-x: auto;
        }

        .ct-routine-table {
            border-collapse: collapse;
            min-width: 980px;
            width: 100%;
        }

        .ct-routine-table th {
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
            color: #334155;
            font-size: 12px;
            font-weight: 800;
            padding: 12px;
            text-align: left;
            text-transform: uppercase;
        }

        .ct-routine-table td {
            border-bottom: 1px solid #eef2f7;
            color: #0f172a;
            font-size: 13px;
            padding: 12px;
            vertical-align: top;
        }

        .ct-routine-table tr:last-child td {
            border-bottom: 0;
        }

        .ct-routine-badge {
            border-radius: 999px;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            padding: 4px 9px;
        }

        .ct-routine-badge.is-ok { background: #dcfce7; color: #166534; }
        .ct-routine-badge.is-warn { background: #fef3c7; color: #92400e; }
        .ct-routine-badge.is-error { background: #fee2e2; color: #991b1b; }
        .ct-routine-badge.is-neutral { background: #e2e8f0; color: #334155; }

        .ct-routine-links {
            display: flex;
            gap: 8px;
            white-space: nowrap;
        }

        .ct-routine-link {
            color: #2563eb;
            font-weight: 800;
            text-decoration: none;
        }

        .ct-routine-link:hover {
            text-decoration: underline;
        }

        .ct-routine-error {
            color: #991b1b;
            max-width: 280px;
        }

        @media (max-width: 900px) {
            .ct-routine-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>

    @php
        $statusClass = fn (?string $status): string => match ($status) {
            'concluido', 'enviado' => 'is-ok',
            'processando', 'pendente_whatsapp', 'simulado' => 'is-warn',
            'erro' => 'is-error',
            default => 'is-neutral',
        };
    @endphp

    <div class="ct-routine-page">
        <section class="ct-routine-summary">
            <div class="ct-routine-stat">
                <span class="ct-routine-label">Data</span>
                <span class="ct-routine-value">{{ $record->data_processamento?->format('d/m/Y') }}</span>
            </div>
            <div class="ct-routine-stat">
                <span class="ct-routine-label">Status</span>
                <span class="ct-routine-value">{{ $record->status }}</span>
            </div>
            <div class="ct-routine-stat">
                <span class="ct-routine-label">Tipo</span>
                <span class="ct-routine-value">{{ $record->tipo ?? '-' }}</span>
            </div>
            <div class="ct-routine-stat">
                <span class="ct-routine-label">Processados</span>
                <span class="ct-routine-value">{{ $record->total_processados }}</span>
            </div>
            <div class="ct-routine-stat">
                <span class="ct-routine-label">Enviados/pendentes</span>
                <span class="ct-routine-value">{{ $record->total_enviados }}</span>
            </div>
            <div class="ct-routine-stat">
                <span class="ct-routine-label">Ignorados</span>
                <span class="ct-routine-value">{{ $record->total_ignorados }}</span>
            </div>
            <div class="ct-routine-stat">
                <span class="ct-routine-label">Erros</span>
                <span class="ct-routine-value">{{ $record->total_erros }}</span>
            </div>
        </section>

        <section class="ct-routine-table-wrap">
            <table class="ct-routine-table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th>Vencimento</th>
                        <th>Valor</th>
                        <th>Telefone</th>
                        <th>Links</th>
                        <th>Erro</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->envios() as $envio)
                        <tr>
                            <td>{{ $envio->cliente?->nome ?? '-' }}</td>
                            <td>{{ $envio->tipo }}</td>
                            <td>
                                <span class="ct-routine-badge {{ $statusClass($envio->status) }}">
                                    {{ $envio->status }}
                                </span>
                            </td>
                            <td>{{ $envio->vencimento?->format('d/m/Y') }}</td>
                            <td>R$ {{ $this->moeda($envio->valor) }}</td>
                            <td>{{ $envio->telefone }}</td>
                            <td>
                                <div class="ct-routine-links">
                                    @if ($envio->link_invoice)
                                        <a class="ct-routine-link" href="{{ $envio->link_invoice }}" target="_blank" rel="noopener noreferrer">Invoice</a>
                                    @endif
                                    @if ($envio->link_boleto)
                                        <a class="ct-routine-link" href="{{ $envio->link_boleto }}" target="_blank" rel="noopener noreferrer">Boleto</a>
                                    @endif
                                </div>
                            </td>
                            <td class="ct-routine-error">{{ $envio->erro }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">Nenhum envio registrado nesta execucao.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
</x-filament-panels::page>
