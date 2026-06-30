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
            display: grid;
            gap: 22px;
            grid-template-columns: minmax(220px, 1fr) minmax(220px, 1fr) auto 260px;
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
            border: 1px solid var(--ct-primary);
            border-radius: 6px;
            color: #b45309;
            cursor: pointer;
            display: inline-flex;
            font-size: 14px;
            font-weight: 700;
            height: 42px;
            justify-content: center;
            padding: 0 18px;
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
            background: transparent;
            border: 0;
            color: var(--ct-primary-strong);
            cursor: pointer;
            font-weight: 700;
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

            <div class="ct-actions">
                    <button
                        type="button"
                        wire:click="limparFiltros"
                        class="ct-btn"
                    >
                        Limpar
                    </button>
                    <button
                        type="button"
                        wire:click="exportarCsv"
                        class="ct-btn"
                    >
                        Exportar
                    </button>
            </div>

            <label class="ct-field">
                <span class="ct-label">Busca</span>
                <input
                    type="search"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Buscar"
                    class="ct-input"
                />
            </label>
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
                        <th>Modelo</th>
                        <th>IMEI</th>
                        <th>Ativacao</th>
                        <th>Status Estoque</th>
                        <th>Tecnico</th>
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
                            <td>{{ $rastreador->imei }}</td>
                            <td>{{ $rastreador->ativacao }}</td>
                            <td>{{ $rastreador->statusRastreador?->label }}</td>
                            <td>{{ $rastreador->tecnico?->nome }}</td>
                            <td>
                                <button
                                    type="button"
                                    wire:click="excluir({{ $rastreador->id }})"
                                    wire:confirm="Deseja excluir este rastreador?"
                                    class="ct-delete"
                                >
                                    Excluir
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="ct-empty">Nenhum rastreador encontrado.</td>
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
