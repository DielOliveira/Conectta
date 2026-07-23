<x-filament-panels::page>
    <style>
        .ct-stock-page {
            --ct-primary: #f59e0b;
            --ct-primary-strong: #d97706;
            --ct-primary-soft: rgba(245, 158, 11, 0.18);
            display: grid;
            gap: 14px;
        }

        .ct-toolbar {
            align-items: end;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            display: grid;
            gap: 12px;
            grid-template-columns: minmax(150px, 190px) minmax(150px, 180px) minmax(220px, 1fr) auto;
            padding: 14px 16px;
        }

        .ct-card {
            background: #ffffff;
            border: 1px solid #d9dee7;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            padding: 22px;
        }

        .ct-form-grid {
            align-items: end;
            display: grid;
            gap: 18px;
            grid-template-columns: 1fr 1fr 1.6fr 1fr 1.35fr auto;
        }

        .ct-field {
            display: grid;
            gap: 6px;
        }

        .ct-label {
            color: #334155;
            font-size: 14px;
            font-weight: 500;
        }

        .ct-input,
        .ct-select {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            color: #0f172a;
            font-size: 15px;
            height: 42px;
            outline: none;
            padding: 0 12px;
            width: 100%;
        }

        .ct-input:focus,
        .ct-select:focus {
            border-color: var(--ct-primary);
            box-shadow: 0 0 0 3px var(--ct-primary-soft);
        }

        .ct-actions {
            display: flex;
            gap: 8px;
        }

        .ct-btn {
            align-items: center;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            color: #374151;
            cursor: pointer;
            display: inline-flex;
            font-size: 14px;
            font-weight: 700;
            height: 42px;
            justify-content: center;
            padding: 0 18px;
            white-space: nowrap;
        }

        .ct-btn:hover {
            background: #f9fafb;
        }

        .ct-icon-btn {
            font-size: 22px;
            padding: 0;
            width: 42px;
        }

        .ct-save {
            background: var(--ct-primary);
            border-color: var(--ct-primary);
            color: #111827;
        }

        .ct-save:hover {
            background: var(--ct-primary);
        }

        .ct-cancel {
            border-color: #cbd5e1;
            color: #475569;
        }

        .ct-table-wrap {
            background: #ffffff;
            border: 1px solid #d9dee7;
            border-radius: 8px;
            overflow: hidden;
        }

        .ct-table {
            border-collapse: collapse;
            font-size: 15px;
            width: 100%;
        }

        .ct-table th {
            background: #f8fafc;
            color: #334155;
            font-weight: 700;
            padding: 14px 18px;
            text-align: left;
        }

        .ct-sort {
            align-items: center;
            background: transparent;
            border: 0;
            color: inherit;
            cursor: pointer;
            display: inline-flex;
            font: inherit;
            font-weight: inherit;
            gap: 6px;
            padding: 0;
        }

        .ct-sort-indicator {
            color: var(--ct-primary-strong);
            font-size: 12px;
        }

        .ct-table td {
            border-top: 1px solid #e2e8f0;
            color: #0f172a;
            padding: 14px 18px;
        }

        .ct-link {
            background: transparent;
            border: 0;
            color: var(--ct-primary-strong);
            cursor: pointer;
            font: inherit;
            font-weight: 500;
            padding: 0;
        }

        .ct-delete {
            align-items: center;
            background: transparent;
            border: 0;
            border-radius: 6px;
            color: #dc2626;
            cursor: pointer;
            display: inline-flex;
            gap: 6px;
            font-weight: 700;
            padding: 6px 8px;
        }

        .ct-delete:hover {
            background: #fef2f2;
            color: #b91c1c;
        }

        .ct-delete svg {
            height: 16px;
            width: 16px;
        }

        .ct-row-actions {
            align-items: center;
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            white-space: nowrap;
        }

        .ct-add-chip {
            align-items: center;
            background: transparent;
            border: 0;
            border-radius: 6px;
            color: #15803d;
            cursor: pointer;
            display: inline-flex;
            gap: 4px;
            font-weight: 700;
            padding: 6px 8px;
        }

        .ct-add-chip:hover {
            background: #f0fdf4;
            color: #166534;
        }

        .ct-add-chip svg,
        .ct-remove-chip svg {
            height: 17px;
            width: 17px;
        }

        .ct-remove-chip {
            align-items: center;
            background: transparent;
            border: 0;
            border-radius: 6px;
            color: #dc2626;
            cursor: pointer;
            display: inline-flex;
            gap: 4px;
            font-weight: 700;
            padding: 6px 8px;
        }

        .ct-remove-chip:hover {
            background: #fef2f2;
            color: #b91c1c;
        }

        .ct-error {
            color: #dc2626;
            font-size: 12px;
        }

        .ct-empty {
            color: #64748b;
            padding: 26px;
            text-align: center;
        }

        .ct-pagination {
            align-items: center;
            color: #64748b;
            display: grid;
            font-size: 14px;
            gap: 16px;
            grid-template-columns: 1fr auto 1fr;
            padding: 4px 8px 0;
        }

        .ct-page-buttons {
            align-items: center;
            display: flex;
            gap: 8px;
        }

        .ct-page-btn {
            align-items: center;
            background: #ffffff;
            border: 1px solid #d9dee7;
            border-radius: 6px;
            color: #334155;
            cursor: pointer;
            display: inline-flex;
            font-size: 14px;
            height: 34px;
            justify-content: center;
            min-width: 34px;
            padding: 0 10px;
        }

        .ct-page-btn-active {
            border-color: var(--ct-primary);
            color: #b45309;
            font-weight: 800;
        }

        .ct-page-btn:disabled {
            background: #f8fafc;
            color: #cbd5e1;
            cursor: not-allowed;
        }

        .ct-page-gap {
            color: #64748b;
            padding: 0 4px;
        }

        @media (max-width: 1100px) {
            .ct-toolbar,
            .ct-form-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 700px) {
            .ct-toolbar,
            .ct-form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="ct-stock-page">
        <div class="ct-toolbar">
            <label class="ct-field">
                <span class="ct-label">Tecnico</span>
                    <select
                        wire:model.live="filtroTecnicoId"
                        class="ct-select"
                    >
                        <option value="">-- Todos --</option>
                        @foreach ($this->tecnicos() as $tecnico)
                            <option value="{{ $tecnico->id }}">{{ $tecnico->nome }}</option>
                        @endforeach
                    </select>
            </label>

            <label class="ct-field">
                <span class="ct-label">Status</span>
                    <select
                        wire:model.live="filtroStatusId"
                        class="ct-select"
                    >
                        <option value="">Todos</option>
                        @foreach ($this->statusOptions() as $status)
                            <option value="{{ $status->id }}">{{ $status->label }}</option>
                        @endforeach
                    </select>
            </label>

            <label class="ct-field">
                <span class="ct-label">Busca</span>
                <input
                    type="search"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Buscar"
                    class="ct-input"
                />
            </label>

            <button
                type="button"
                wire:click="limparFiltros"
                class="ct-btn"
            >
                Limpar
            </button>
        </div>

        <div class="ct-card">
            <div class="ct-form-grid">
                <label class="ct-field">
                    <span class="ct-label">Modelo *</span>
                    <input wire:model="modelo" type="text" class="ct-input" />
                    @error('modelo') <span class="ct-error">{{ $message }}</span> @enderror
                </label>

                <label class="ct-field">
                    <span class="ct-label">Ativacao</span>
                    <input wire:model="ativacao" type="number" class="ct-input" />
                    @error('ativacao') <span class="ct-error">{{ $message }}</span> @enderror
                </label>

                <label class="ct-field">
                    <span class="ct-label">IMEI *</span>
                    <input wire:model="imei" type="text" class="ct-input" />
                    @error('imei') <span class="ct-error">{{ $message }}</span> @enderror
                </label>

                <label class="ct-field">
                    <span class="ct-label">Status Estoque</span>
                    <select wire:model="status_rastreador_id" class="ct-select">
                        <option value="">--</option>
                        @foreach ($this->statusOptions() as $status)
                            <option value="{{ $status->id }}">{{ $status->label }}</option>
                        @endforeach
                    </select>
                    @error('status_rastreador_id') <span class="ct-error">{{ $message }}</span> @enderror
                </label>

                <label class="ct-field">
                    <span class="ct-label">Tecnico</span>
                    <select wire:model="tecnico_id" class="ct-select">
                        <option value="">--</option>
                        @foreach ($this->tecnicos() as $tecnico)
                            <option value="{{ $tecnico->id }}">{{ $tecnico->nome }}</option>
                        @endforeach
                    </select>
                    @error('tecnico_id') <span class="ct-error">{{ $message }}</span> @enderror
                </label>

                <div class="ct-actions">
                    <button
                        type="button"
                        wire:click="salvar"
                        class="ct-btn ct-icon-btn ct-save"
                        title="{{ $editingId ? 'Salvar' : 'Adicionar' }}"
                    >
                        {{ $editingId ? '✓' : '+' }}
                    </button>
                    @if ($editingId)
                        <button
                            type="button"
                            wire:click="limparFormulario"
                            class="ct-btn ct-icon-btn ct-cancel"
                            title="Cancelar edicao"
                        >
                            X
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="ct-table-wrap">
            <table class="ct-table">
                <thead>
                    <tr>
                        @foreach ([
                            'modelo' => 'Modelo',
                            'numero_chip' => 'Numero Chip',
                            'imei' => 'IMEI',
                            'ativacao' => 'Ativacao',
                            'status' => 'Status Estoque',
                            'tecnico' => 'Tecnico',
                        ] as $campo => $rotulo)
                            <th>
                                <button type="button" wire:click="ordenarPor('{{ $campo }}')" class="ct-sort">
                                    <span>{{ $rotulo }}</span>
                                    @if ($ordenacao === $campo)
                                        <span class="ct-sort-indicator">
                                            {{ $direcaoOrdenacao === 'asc' ? '▲' : '▼' }}
                                        </span>
                                    @endif
                                </button>
                            </th>
                        @endforeach
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->rastreadores() as $rastreador)
                        <tr wire:key="rastreador-{{ $rastreador->id }}">
                            <td>
                                <button type="button" wire:click="editar({{ $rastreador->id }})" class="ct-link">
                                    {{ $rastreador->modelo }}
                                </button>
                            </td>
                            <td>{{ $rastreador->chip?->numero_chip ?: '-' }}</td>
                            <td>{{ $rastreador->imei }}</td>
                            <td>{{ $rastreador->ativacao }}</td>
                            <td>{{ $rastreador->statusRastreador?->label }}</td>
                            <td>{{ $rastreador->tecnico?->nome }}</td>
                            <td>
                                <div class="ct-row-actions">
                                    @if (! $rastreador->chip_id && auth()->user()?->hasPermission(\App\Models\Permission::ESTOQUE_ESCRITA))
                                        <button
                                            type="button"
                                            wire:click="mountAction('adicionarChip', { id: {{ $rastreador->id }} })"
                                            class="ct-add-chip"
                                            title="Adicionar chip"
                                        >
                                            <x-filament::icon icon="heroicon-m-plus" />
                                            <span>Chip</span>
                                        </button>
                                    @elseif ($rastreador->chip_id && auth()->user()?->hasPermission(\App\Models\Permission::ESTOQUE_ESCRITA))
                                        <button
                                            type="button"
                                            wire:click="mountAction('removerChip', { id: {{ $rastreador->id }} })"
                                            class="ct-remove-chip"
                                            title="Remover chip"
                                        >
                                            <x-filament::icon icon="heroicon-m-minus" />
                                            <span>Chip</span>
                                        </button>
                                    @endif

                                    <button
                                        type="button"
                                        wire:click="mountAction('confirmarExclusao', { id: {{ $rastreador->id }} })"
                                        class="ct-delete"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673A2.25 2.25 0 0 1 15.916 21H8.084a2.25 2.25 0 0 1-2.244-1.327L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916A2.25 2.25 0 0 0 13.5 2.25h-3A2.25 2.25 0 0 0 8.25 4.5v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                        <span>Excluir</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="ct-empty">Nenhum rastreador encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="ct-pagination">
            <div>
                {{ $this->inicioPagina() }} a {{ $this->fimPagina() }} de {{ $this->totalRastreadores() }} registros
            </div>

            <div class="ct-page-buttons">
                <button
                    type="button"
                    wire:click="paginaAnterior"
                    class="ct-page-btn"
                    @disabled($this->pagina <= 1)
                    title="Pagina anterior"
                >
                    &lsaquo;
                </button>

                @foreach ($this->paginasVisiveis() as $pagina)
                    @if ($pagina === '...')
                        <span class="ct-page-gap">...</span>
                    @else
                        <button
                            type="button"
                            wire:click="irParaPagina({{ $pagina }})"
                            class="ct-page-btn {{ $pagina === $this->pagina ? 'ct-page-btn-active' : '' }}"
                        >
                            {{ $pagina }}
                        </button>
                    @endif
                @endforeach

                <button
                    type="button"
                    wire:click="paginaProxima"
                    class="ct-page-btn"
                    @disabled($this->pagina >= $this->totalPaginas())
                    title="Proxima pagina"
                >
                    &rsaquo;
                </button>
            </div>

            <div></div>
        </div>
    </div>
</x-filament-panels::page>
