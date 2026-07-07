<x-filament-panels::page>
    <style>
        .ct-history-page {
            display: grid;
            gap: 18px;
            width: 100%;
        }

        .ct-history-filterbar {
            align-items: end;
            display: grid;
            gap: 14px;
            grid-template-columns: 220px 90px 96px;
            max-width: 520px;
        }

        .ct-history-field {
            display: grid;
            gap: 6px;
        }

        .ct-history-label {
            color: #374151;
            font-size: 13px;
            font-weight: 600;
        }

        .ct-history-input,
        .ct-history-select {
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            color: #111827;
            font-size: 14px;
            height: 42px;
            padding: 0 12px;
            width: 100%;
        }

        .ct-history-btn {
            background: #4f63d8;
            border: 0;
            border-radius: 6px;
            color: #ffffff;
            cursor: pointer;
            font-size: 14px;
            font-weight: 800;
            height: 42px;
            padding: 0 18px;
        }

        .ct-history-table-wrap {
            background: #ffffff;
            border: 1px solid #d9dee7;
            border-radius: 8px;
            overflow: hidden;
        }

        .ct-history-table {
            border-collapse: collapse;
            table-layout: fixed;
            width: 100%;
        }

        .ct-history-table th {
            background: #ffffff;
            border-bottom: 1px solid #d9dee7;
            color: #4b5563;
            font-size: 13px;
            font-weight: 800;
            height: 48px;
            padding: 0 14px;
            text-align: left;
        }

        .ct-history-table td {
            border-bottom: 1px solid #d9dee7;
            color: #111827;
            font-size: 13px;
            height: 64px;
            overflow: hidden;
            padding: 8px 14px;
            text-overflow: ellipsis;
            vertical-align: middle;
            white-space: nowrap;
        }

        .ct-history-table .ct-wrap {
            line-height: 1.35;
            white-space: normal;
        }

        .ct-history-empty {
            color: #6b7280;
            height: 80px;
            text-align: center;
        }

        .ct-history-pagination {
            align-items: center;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            color: #374151;
            display: grid;
            font-size: 14px;
            gap: 16px;
            grid-template-columns: 1fr auto 1fr;
            padding: 14px 18px;
        }

        .ct-history-page-buttons {
            align-items: center;
            display: inline-flex;
            justify-self: end;
            gap: 6px;
        }

        .ct-history-page-btn {
            background: #ffffff;
            border: 1px solid #d9dee7;
            border-radius: 6px;
            color: #334155;
            cursor: pointer;
            height: 34px;
            min-width: 34px;
            padding: 0 10px;
        }

        .ct-history-page-btn-active {
            border-color: #4f63d8;
            color: #4f63d8;
            font-weight: 800;
        }

        .ct-history-page-btn:disabled {
            color: #cbd5e1;
            cursor: not-allowed;
        }
    </style>

    @php
        $registros = $this->registros();
        $total = $this->totalRegistros();
        $inicio = $this->inicioPagina();
        $fim = $this->fimPagina();
        $totalPaginas = $this->totalPaginas();
    @endphp

    <div class="ct-history-page">
        <div class="ct-history-filterbar">
            <label class="ct-history-field">
                <span class="ct-history-label">Data</span>
                <input type="date" wire:model.live="data" class="ct-history-input" />
            </label>

            <button type="button" wire:click="$refresh" class="ct-history-btn">Filtrar</button>
            <button type="button" wire:click="limparFiltros" class="ct-history-btn">Hoje</button>
        </div>

        <div class="ct-history-table-wrap">
            <table class="ct-history-table">
                <colgroup>
                    <col style="width: 12%" />
                    <col style="width: 9%" />
                    <col style="width: 11%" />
                    <col style="width: 12%" />
                    <col style="width: 11%" />
                    <col style="width: 12%" />
                    <col style="width: 10%" />
                    <col style="width: 10%" />
                    <col style="width: 10%" />
                    <col style="width: 8%" />
                </colgroup>
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Refer&ecirc;ncia</th>
                        <th>Valor Anterior</th>
                        <th>Valor Modificado</th>
                        <th>Data Anterior</th>
                        <th>Data Modificada</th>
                        <th>Total Antes</th>
                        <th>Total Depois</th>
                        <th>Data Transa&ccedil;&atilde;o</th>
                        <th>Operador</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($registros as $registro)
                        <tr>
                            <td class="ct-wrap" title="{{ $registro['cliente'] }}">{{ $registro['cliente'] }}</td>
                            <td>{{ $registro['referencia'] }}</td>
                            <td>{{ $this->moeda($registro['valor_anterior']) }}</td>
                            <td>{{ $this->moeda($registro['valor_modificado']) }}</td>
                            <td>{{ $this->dataSomente($registro['data_anterior']) }}</td>
                            <td>{{ $this->dataSomente($registro['data_modificada']) }}</td>
                            <td>{{ $this->moeda($registro['total_antes']) }}</td>
                            <td>{{ $this->moeda($registro['total_depois']) }}</td>
                            <td class="ct-wrap">{{ $this->dataHora($registro['data_transacao']) }}</td>
                            <td class="ct-wrap" title="{{ $registro['operador'] }}">{{ $registro['operador'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="ct-history-empty">Nenhuma alteracao financeira encontrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="ct-history-pagination">
            <div>Exibindo {{ $inicio }} a {{ $fim }} de {{ number_format($total, 0, ',', '.') }} resultados</div>

            <label class="ct-history-field">
                <select wire:model.live="porPagina" class="ct-history-select">
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </label>

            <div class="ct-history-page-buttons">
                <button type="button" wire:click="paginaAnterior" class="ct-history-page-btn" @disabled($this->pagina <= 1)>&lt;</button>

                @foreach ($this->paginasVisiveis() as $pagina)
                    @if ($pagina === '...')
                        <span class="ct-history-page-btn">...</span>
                    @else
                        <button
                            type="button"
                            wire:click="irParaPagina({{ $pagina }})"
                            class="ct-history-page-btn {{ $pagina === $this->pagina ? 'ct-history-page-btn-active' : '' }}"
                        >
                            {{ $pagina }}
                        </button>
                    @endif
                @endforeach

                <button type="button" wire:click="paginaProxima" class="ct-history-page-btn" @disabled($this->pagina >= $totalPaginas)>&gt;</button>
            </div>
        </div>
    </div>
</x-filament-panels::page>
