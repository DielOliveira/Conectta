<x-filament-panels::page>
    <style>
        .ct-report-page {
            --ct-primary: #f59e0b;
            display: grid;
            gap: 16px;
            width: 100%;
        }


        .ct-report-top-actions {
            display: flex;
            justify-content: flex-end;
        }

        .ct-report-top-actions .ct-report-btn {
            width: auto;
        }
        .ct-report-filterbar {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            display: grid;
            gap: 14px;
            grid-template-columns: 170px 170px 190px 190px 190px 96px;
            padding: 18px 24px;
            overflow-x: auto;
        }

        .ct-report-field {
            display: grid;
            gap: 7px;
        }

        .ct-report-label {
            color: #111827;
            font-size: 13px;
            font-weight: 600;
        }

        .ct-report-input,
        .ct-report-select {
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            color: #111827;
            font-size: 14px;
            height: 42px;
            outline: none;
            padding: 0 12px;
            width: 100%;
        }

        .ct-report-input:focus,
        .ct-report-select:focus {
            border-color: var(--ct-primary);
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.18);
        }

        .ct-report-actions {
            align-items: end;
            display: flex;
            gap: 10px;
        }

        .ct-report-btn {
            align-items: center;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            color: #111827;
            cursor: pointer;
            display: inline-flex;
            font-size: 14px;
            font-weight: 700;
            gap: 8px;
            height: 42px;
            justify-content: center;
            padding: 0 14px;
            width: 100%;
        }

        .ct-report-btn:hover {
            background: #f9fafb;
        }

        .ct-report-btn svg {
            color: #9ca3af;
            height: 18px;
            width: 18px;
        }

        .ct-report-table-wrap {
            background: #ffffff;
            border: 1px solid #d9dee7;
            border-radius: 8px;
            overflow: hidden;
        }

        .ct-report-table {
            border-collapse: collapse;
            table-layout: fixed;
            width: 100%;
        }

        .ct-report-table th {
            background: #f8fafc;
            border-bottom: 1px solid #d9dee7;
            color: #1f2937;
            font-size: 13px;
            font-weight: 800;
            height: 48px;
            padding: 0 16px;
            text-align: left;
        }

        .ct-report-table td {
            border-bottom: 1px solid #d9dee7;
            color: #0f172a;
            font-size: 13px;
            height: 58px;
            overflow: hidden;
            padding: 0 16px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .ct-report-status-cell {
            overflow: visible !important;
            white-space: normal !important;
        }

        .ct-report-badge {
            border-radius: 999px;
            display: inline-flex;
            font-size: 11px;
            font-weight: 800;
            line-height: 1.15;
            max-width: 100%;
            padding: 6px 10px;
            text-align: center;
            white-space: normal;
        }

        .ct-report-badge-warning { background: #fef3c7; color: #92400e; }
        .ct-report-badge-success { background: #dcfce7; color: #166534; }
        .ct-report-badge-danger { background: #fee2e2; color: #b91c1c; }
        .ct-report-badge-orange { background: #ffedd5; color: #c2410c; }
        .ct-report-badge-neutral { background: #e2e8f0; color: #334155; }

        .ct-report-empty {
            color: #64748b;
            padding: 26px;
            text-align: center;
        }

        .ct-report-pagination {
            align-items: center;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 0 0 8px 8px;
            color: #374151;
            display: grid;
            font-size: 14px;
            gap: 16px;
            grid-template-columns: 1fr auto 1fr;
            margin-top: -16px;
            padding: 14px 24px;
        }

        .ct-report-page-buttons {
            align-items: center;
            border: 1px solid #d9dee7;
            border-radius: 8px;
            display: inline-flex;
            justify-self: end;
            overflow: hidden;
        }

        .ct-report-page-btn {
            align-items: center;
            background: #ffffff;
            border: 0;
            border-right: 1px solid #d9dee7;
            color: #334155;
            cursor: pointer;
            display: inline-flex;
            font-size: 14px;
            height: 36px;
            justify-content: center;
            min-width: 48px;
            padding: 0 14px;
        }

        .ct-report-page-btn:last-child {
            border-right: 0;
        }

        .ct-report-page-btn-active {
            color: #b45309;
            font-weight: 800;
        }

        .ct-report-page-btn:disabled {
            background: #f8fafc;
            color: #cbd5e1;
            cursor: not-allowed;
        }

        .ct-report-page-size-wrap {
            align-items: center;
            border: 1px solid #d9dee7;
            border-radius: 8px;
            display: inline-flex;
            justify-self: center;
            overflow: hidden;
        }

        .ct-report-page-size-label {
            background: #ffffff;
            border-right: 1px solid #d9dee7;
            color: #6b7280;
            height: 36px;
            line-height: 36px;
            padding: 0 14px;
            white-space: nowrap;
        }

        .ct-report-page-size {
            border: 0;
            border-radius: 0;
            height: 36px;
            padding: 0 34px 0 12px;
            width: 78px;
        }
        @media (max-width: 1300px) {
            .ct-report-filterbar {
                grid-template-columns: repeat(3, minmax(180px, 1fr));
            }
        }

        @media (max-width: 800px) {
            .ct-report-filterbar,
            .ct-report-pagination {
                grid-template-columns: 1fr;
            }

            .ct-report-page-buttons,
            .ct-report-page-size-wrap {
                justify-self: start;
            }
        }
    </style>

    @php($total = $this->totalLancamentos())
    @php($totalPaginas = $this->totalPaginas())
    @php($inicio = $total === 0 ? 0 : (($pagina - 1) * $porPagina) + 1)
    @php($fim = min($total, $pagina * $porPagina))
    @php($primeiraPagina = max(1, $pagina - 2))
    @php($ultimaPagina = min($totalPaginas, $pagina + 2))
    @php($mostrarInicio = $primeiraPagina > 1)
    @php($mostrarFim = $ultimaPagina < $totalPaginas)

    <div class="ct-report-page">
        <div class="ct-report-top-actions">
            <button type="button" wire:click="exportarCsv" class="ct-report-btn">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M7.5 12 12 16.5m0 0 4.5-4.5M12 16.5V3" /></svg>
                <span>Exportar</span>
            </button>
        </div>
        <div class="ct-report-filterbar">
            <label class="ct-report-field">
                <span class="ct-report-label">Data In&iacute;cio</span>
                <input type="date" wire:model.live="dataInicio" class="ct-report-input" />
            </label>

            <label class="ct-report-field">
                <span class="ct-report-label">Data Fim</span>
                <input type="date" wire:model.live="dataFim" class="ct-report-input" />
            </label>

            <label class="ct-report-field">
                <span class="ct-report-label">N. Boleto</span>
                <input type="text" wire:model.live.debounce.500ms="numeroBoleto" class="ct-report-input" />
            </label>

            <label class="ct-report-field">
                <span class="ct-report-label">Status Cliente</span>
                <select wire:model.live="statusCliente" class="ct-report-select">
                    <option value="0">Todos</option>
                    <option value="1">Ativo</option>
                    <option value="2">Inativo</option>
                </select>
            </label>

            <label class="ct-report-field">
                <span class="ct-report-label">Status Boleto</span>
                <select wire:model.live="statusBoleto" class="ct-report-select">
                    <option value="">Todos</option>
                    @foreach ($this->statusBoletos() as $status)
                        <option value="{{ $status['value'] }}">{{ $status['label'] }}</option>
                    @endforeach
                </select>
            </label>

            <div class="ct-report-actions">
                <button type="button" wire:click="limparFiltros" class="ct-report-btn">Limpar</button>
            </div>

        </div>

        <div class="ct-report-table-wrap">
            <table class="ct-report-table">
                <colgroup>
                    <col style="width: 35%" />
                    <col style="width: 12%" />
                    <col style="width: 12%" />
                    <col style="width: 11%" />
                    <col style="width: 13%" />
                    <col style="width: 17%" />
                </colgroup>
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Vencimento</th>
                        <th>M&ecirc;s / Ano</th>
                        <th>Valor</th>
                        <th>N. Boleto</th>
                        <th>Status Boleto</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->lancamentos() as $lancamento)
                        <tr wire:key="relatorio-lancamento-{{ $lancamento->id }}">
                            <td title="{{ $lancamento->cliente?->nome }}">{{ $lancamento->cliente?->nome }}</td>
                            <td>{{ $lancamento->cliente?->dia_pagamento }}</td>
                            <td>{{ $lancamento->mes_referencia }} / {{ $lancamento->ano_referencia }}</td>
                            <td>{{ $this->moeda($lancamento->valor_planejado) }}</td>
                            <td>{{ $lancamento->numero_boleto }}</td>
                            <td class="ct-report-status-cell">
                                @if (filled($lancamento->invoice?->status))
                                    <span class="ct-report-badge {{ $this->statusBoletoClasse($lancamento->invoice->status) }}">
                                        {{ $this->statusBoletoLabel($lancamento->invoice->status) }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="ct-report-empty">Nenhum lan&ccedil;amento aberto encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="ct-report-pagination">
            <div>Exibindo {{ $inicio }} a {{ $fim }} de {{ number_format($total, 0, ',', '.') }} resultados</div>

            <div class="ct-report-page-size-wrap">
                <span class="ct-report-page-size-label">por p&aacute;gina</span>
                <select wire:model.live="porPagina" class="ct-report-select ct-report-page-size">
                    <option value="8">8</option>
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>

            <div class="ct-report-page-buttons">
                <button type="button" wire:click="paginaAnterior" class="ct-report-page-btn" @disabled($pagina <= 1)>&lt;</button>

                @if ($mostrarInicio)
                    <button type="button" wire:click="irParaPagina(1)" class="ct-report-page-btn">1</button>
                    @if ($primeiraPagina > 2)
                        <span class="ct-report-page-btn">...</span>
                    @endif
                @endif

                @for ($page = $primeiraPagina; $page <= $ultimaPagina; $page++)
                    <button
                        type="button"
                        wire:click="irParaPagina({{ $page }})"
                        class="ct-report-page-btn {{ $page === $pagina ? 'ct-report-page-btn-active' : '' }}"
                    >
                        {{ $page }}
                    </button>
                @endfor

                @if ($mostrarFim)
                    @if ($ultimaPagina < $totalPaginas - 1)
                        <span class="ct-report-page-btn">...</span>
                    @endif
                    <button type="button" wire:click="irParaPagina({{ $totalPaginas }})" class="ct-report-page-btn">{{ $totalPaginas }}</button>
                @endif

                <button type="button" wire:click="proximaPagina" class="ct-report-page-btn" @disabled($pagina >= $totalPaginas)>&gt;</button>
            </div>
        </div>
    </div>
</x-filament-panels::page>