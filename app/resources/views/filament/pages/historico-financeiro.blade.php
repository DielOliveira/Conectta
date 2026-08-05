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
            grid-template-columns: minmax(220px, 320px) 220px minmax(180px, 240px) 90px 96px;
            max-width: 1030px;
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

        .ct-history-searchable {
            position: relative;
        }

        .ct-history-searchable-trigger {
            align-items: center;
            display: flex;
            justify-content: space-between;
            text-align: left;
        }

        .ct-history-searchable-trigger svg {
            color: #6b7280;
            height: 16px;
            width: 16px;
        }

        .ct-history-searchable-menu {
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 7px;
            box-shadow: 0 10px 24px rgb(15 23 42 / 14%);
            left: 0;
            padding: 6px;
            position: absolute;
            right: 0;
            top: calc(100% + 5px);
            z-index: 30;
        }

        .ct-history-searchable-menu input {
            height: 38px;
            margin-bottom: 5px;
        }

        .ct-history-searchable-options {
            max-height: 240px;
            overflow-y: auto;
        }

        .ct-history-searchable-option {
            background: transparent;
            border: 0;
            border-radius: 5px;
            color: #111827;
            cursor: pointer;
            display: block;
            font-size: 14px;
            padding: 9px 10px;
            text-align: left;
            width: 100%;
        }

        .ct-history-searchable-option:hover,
        .ct-history-searchable-option-active {
            background: #eef2ff;
            color: #4338ca;
        }

        .ct-history-searchable-empty {
            color: #6b7280;
            font-size: 13px;
            padding: 10px;
            text-align: center;
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

        @media (max-width: 720px) {
            .ct-history-filterbar {
                grid-template-columns: 1fr 1fr;
                max-width: none;
            }
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
                <span class="ct-history-label">Cliente</span>
                <input
                    type="search"
                    wire:model.live.debounce.300ms="cliente"
                    class="ct-history-input"
                    placeholder="Pesquisar pelo nome"
                />
            </label>

            <label class="ct-history-field">
                <span class="ct-history-label">Data</span>
                <input type="date" wire:model.live="data" class="ct-history-input" />
            </label>

            <div class="ct-history-field">
                <span class="ct-history-label">Operador</span>
                @php
                    $operadores = $this->operadores()
                        ->map(fn (string $nome, int|string $id): array => ['value' => (string) $id, 'label' => $nome])
                        ->values();
                @endphp
                <div
                    class="ct-history-searchable"
                    x-data="{
                        open: false,
                        search: '',
                        state: @entangle('operador').live,
                        options: @js($operadores),
                        get filteredOptions() {
                            const term = this.search.trim().toLocaleLowerCase('pt-BR')
                            return term === ''
                                ? this.options
                                : this.options.filter(option => option.label.toLocaleLowerCase('pt-BR').includes(term))
                        },
                        get selectedLabel() {
                            return this.options.find(option => option.value === String(this.state))?.label ?? 'Todos'
                        },
                        select(value) {
                            this.state = value
                            this.open = false
                            this.search = ''
                        },
                    }"
                    x-on:click.outside="open = false; search = ''"
                >
                    <button
                        type="button"
                        class="ct-history-input ct-history-searchable-trigger"
                        x-on:click="open = ! open; if (open) $nextTick(() => $refs.search.focus())"
                        x-bind:aria-expanded="open"
                    >
                        <span x-text="selectedLabel"></span>
                        <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 0 1 1.06 0L10 10.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div class="ct-history-searchable-menu" x-show="open" x-cloak>
                        <input
                            type="search"
                            class="ct-history-input"
                            placeholder="Pesquisar operador"
                            x-model="search"
                            x-ref="search"
                            x-on:keydown.escape="open = false; search = ''"
                        />
                        <div class="ct-history-searchable-options">
                            <button
                                type="button"
                                class="ct-history-searchable-option"
                                x-bind:class="{ 'ct-history-searchable-option-active': state === '' }"
                                x-on:click="select('')"
                            >Todos</button>
                            <template x-for="option in filteredOptions" x-bind:key="option.value">
                                <button
                                    type="button"
                                    class="ct-history-searchable-option"
                                    x-bind:class="{ 'ct-history-searchable-option-active': String(state) === option.value }"
                                    x-on:click="select(option.value)"
                                    x-text="option.label"
                                ></button>
                            </template>
                            <div class="ct-history-searchable-empty" x-show="filteredOptions.length === 0">Nenhum usu&aacute;rio encontrado.</div>
                        </div>
                    </div>
                </div>
            </div>

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
