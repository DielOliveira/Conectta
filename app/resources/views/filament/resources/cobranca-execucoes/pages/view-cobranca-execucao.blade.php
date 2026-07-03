<x-filament-panels::page>
    <style>
        .ct-routine-page {
            display: grid;
            gap: 18px;
        }

        .ct-routine-panel {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 18px;
        }

        .ct-routine-head {
            display: grid;
            gap: 18px;
            grid-template-columns: minmax(280px, 1.1fr) minmax(320px, 2fr);
        }

        .ct-routine-title {
            color: #0f172a;
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .ct-routine-muted {
            color: #64748b;
            font-size: 13px;
        }

        .ct-routine-summary {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .ct-routine-stat {
            background: #f8fafc;
            border: 1px solid #eef2f7;
            border-radius: 6px;
            display: grid;
            gap: 6px;
            min-width: 0;
            padding: 12px;
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
            overflow-wrap: anywhere;
        }

        .ct-routine-meta {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-top: 14px;
        }

        .ct-routine-table-wrap {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow-x: auto;
        }

        .ct-routine-table {
            border-collapse: collapse;
            min-width: 1120px;
            table-layout: fixed;
            width: 100%;
        }

        .ct-routine-table th:nth-child(1),
        .ct-routine-table td:nth-child(1) { width: 240px; }
        .ct-routine-table th:nth-child(2),
        .ct-routine-table td:nth-child(2) { width: 140px; }
        .ct-routine-table th:nth-child(3),
        .ct-routine-table td:nth-child(3) { width: 145px; }
        .ct-routine-table th:nth-child(4),
        .ct-routine-table td:nth-child(4) { width: 105px; }
        .ct-routine-table th:nth-child(5),
        .ct-routine-table td:nth-child(5) { width: 95px; }
        .ct-routine-table th:nth-child(6),
        .ct-routine-table td:nth-child(6) { width: 130px; }
        .ct-routine-table th:nth-child(7),
        .ct-routine-table td:nth-child(7) { width: 120px; }

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

        .ct-routine-cell-main {
            font-weight: 700;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .ct-routine-error {
            color: #991b1b;
            max-width: 100%;
            overflow-wrap: anywhere;
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

        @media (max-width: 900px) {
            .ct-routine-head,
            .ct-routine-meta {
                grid-template-columns: 1fr;
            }

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
        <section class="ct-routine-panel">
            <div class="ct-routine-head">
                <div>
                    <div class="ct-routine-title">{{ $record->tipo ?? 'Execucao de cobranca' }}</div>
                    <div class="ct-routine-muted">
                        Processamento de {{ $record->data_processamento?->format('d/m/Y') }}.
                        {{ $record->dry_run ? 'Simulacao' : 'Execucao real' }}.
                    </div>
                </div>

                <div class="ct-routine-summary">
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
                </div>
            </div>

            <div class="ct-routine-meta">
                <div class="ct-routine-stat">
                    <span class="ct-routine-label">Status</span>
                    <span class="ct-routine-value">{{ $record->status }}</span>
                </div>
                <div class="ct-routine-stat">
                    <span class="ct-routine-label">Iniciou</span>
                    <span class="ct-routine-value">{{ $record->iniciado_em?->format('d/m/Y H:i') ?? '-' }}</span>
                </div>
                <div class="ct-routine-stat">
                    <span class="ct-routine-label">Finalizou</span>
                    <span class="ct-routine-value">{{ $record->finalizado_em?->format('d/m/Y H:i') ?? '-' }}</span>
                </div>
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
                            <td><div class="ct-routine-cell-main" title="{{ $envio->cliente?->nome ?? '-' }}">{{ $envio->cliente?->nome ?? '-' }}</div></td>
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
