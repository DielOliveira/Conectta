<x-filament-panels::page>
    <style>
        .ct-fin-page {
            --ct-primary: #f59e0b;
            --ct-primary-strong: #d97706;
            --ct-primary-soft: rgba(245, 158, 11, 0.18);
            display: grid;
            gap: 18px;
            margin-top: 8px;
        }

        .fi-no-notifications,
        .fi-notifications,
        [data-notifications],
        [x-data*="notifications"],
        [wire\:stream*="notifications"] {
            position: fixed !important;
            z-index: 99999 !important;
        }

        .fi-notifications > *,
        [data-notifications] > *,
        [x-data*="notifications"] > * {
            z-index: 99999 !important;
        }

        .ct-fin-toolbar {
            align-items: end;
            display: grid;
            gap: 16px;
            grid-template-columns: 265px 265px 116px 116px 116px 116px 116px 1fr;
        }

        .ct-fin-field {
            display: grid;
            gap: 6px;
        }

        .ct-fin-label {
            color: #334155;
            font-size: 14px;
            font-weight: 500;
        }

        .ct-fin-input,
        .ct-fin-select {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            color: #0f172a;
            font-size: 14px;
            height: 40px;
            outline: none;
            padding: 0 10px;
            width: 100%;
        }

        .ct-fin-text-input {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            color: #0f172a;
            font-size: 13px;
            height: 34px;
            padding: 0 7px;
            width: 100%;
        }

        .ct-error {
            color: #dc2626;
            font-size: 12px;
        }

        .ct-fin-input:focus,
        .ct-fin-select:focus,
        .ct-fin-text-input:focus {
            border-color: var(--ct-primary);
            box-shadow: 0 0 0 3px var(--ct-primary-soft);
        }

        .ct-fin-btn {
            align-items: center;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            color: #111827;
            cursor: pointer;
            display: inline-flex;
            font-size: 14px;
            font-weight: 700;
            height: 40px;
            justify-content: center;
            min-width: 0;
            padding: 0 15px;
        }

        .ct-fin-btn:hover {
            background: #f9fafb;
        }

        .ct-fin-btn:disabled {
            border-color: #cbd5e1;
            color: #94a3b8;
            cursor: not-allowed;
        }
        .ct-fin-arrow {
            align-items: center;
            background: transparent;
            border: 0;
            color: var(--ct-primary-strong);
            cursor: pointer;
            display: inline-flex;
            font-weight: 800;
            height: 28px;
            justify-content: center;
            width: 28px;
        }

        .ct-fin-arrow svg {
            height: 22px;
            stroke-width: 3;
            width: 22px;
        }

        .ct-fin-grid-wrap {
            padding-bottom: 8px;
            padding-top: 4px;
        }

        .ct-fin-btn-export {
            border-color: #d1d5db;
            color: #111827;
            gap: 8px;
        }

        .ct-fin-btn-export:hover {
            background: #f9fafb;
        }

        .ct-fin-btn-export svg {
            color: #9ca3af;
            height: 18px;
            width: 18px;
        }

        .ct-fin-grid {
            display: grid;
            column-gap: 2px;
            grid-template-columns: minmax(500px, 38%) minmax(0, 31%) minmax(0, 31%);
            width: 100%;
        }

        .ct-fin-section-title {
            align-items: end;
            display: flex;
            font-size: 20px;
            font-weight: 800;
            justify-content: space-between;
            height: 64px;
            padding: 0 6px 0 0;
        }

        .ct-fin-month-title {
            background: #e4e8ed;
            color: #0f172a;
            font-size: 16px;
            font-weight: 700;
            line-height: 26px;
            min-height: 26px;
            padding: 0 10px;
            text-align: center;
        }

        .ct-fin-month-filters {
            display: grid;
            grid-template-columns: 21% 24% 22% 33%;
        }

        .ct-fin-filter-cell {
            align-items: center;
            background: #ffffff;
            display: flex;
            height: 38px;
            justify-content: center;
        }

        .ct-fin-filter-input {
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            color: #0f172a;
            font-size: 13px;
            height: 28px;
            padding: 0 8px;
            width: 100%;
        }

        .ct-fin-tristate {
            align-items: center;
            background: transparent;
            border: 0;
            color: #1f2937;
            cursor: pointer;
            display: inline-flex;
            height: 24px;
            justify-content: center;
            min-width: 24px;
            padding: 0;
        }

        .ct-fin-tristate svg {
            height: 15px;
            width: 15px;
        }

        .ct-fin-tristate-filled {
            color: #1f2937;
        }

        .ct-fin-tristate-empty {
            color: #1f2937;
        }

        .ct-fin-table {
            border-collapse: collapse;
            table-layout: fixed;
            width: 100%;
        }

        .ct-fin-table th {
            background: #f8fafc;
            border: 1px solid #d9dee7;
            color: #334155;
            font-size: 13px;
            font-weight: 800;
            height: 42px;
            padding: 6px;
            text-align: left;
        }

        .ct-fin-table td {
            background: #ffffff;
            border: 1px solid #d9dee7;
            color: #0f172a;
            font-size: 13px;
            height: 48px;
            overflow: hidden;
            padding: 4px 6px;
            text-overflow: ellipsis;
            vertical-align: middle;
            white-space: nowrap;
        }

        .ct-fin-total-row {
            align-items: center;
            display: grid;
            font-size: 12px;
            font-weight: 700;
            gap: 6px;
            grid-template-columns: 21% 24% 22% 33%;
            min-height: 30px;
            padding: 0 6px;
        }

        .ct-fin-total-label {
            text-align: center;
        }

        .ct-fin-total-planejado {
            color: #dc2626;
            font-size: 12px;
            line-height: 1.1;
            text-align: right;
            white-space: nowrap;
        }

        .ct-fin-total-efetivado {
            color: #15803d;
            font-size: 12px;
            line-height: 1.1;
            text-align: left;
            white-space: nowrap;
        }

        .ct-fin-pagination {
            align-items: center;
            color: #64748b;
            display: grid;
            font-size: 14px;
            gap: 16px;
            grid-template-columns: 1fr auto 1fr;
            padding: 8px 32px 0;
        }

        .ct-fin-page-buttons {
            align-items: center;
            display: flex;
            gap: 8px;
        }

        .ct-fin-page-btn {
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

        .ct-fin-page-btn-active {
            border-color: var(--ct-primary);
            color: #b45309;
            font-weight: 800;
        }

        .ct-fin-page-btn:disabled {
            background: #f8fafc;
            color: #cbd5e1;
            cursor: not-allowed;
        }

        .ct-fin-page-gap {
            color: #64748b;
            padding: 0 4px;
        }

        .ct-fin-number {
            text-align: center;
        }

        .ct-fin-pencil {
            background: transparent;
            border: 0;
            color: var(--ct-primary-strong);
            cursor: pointer;
            font-size: 17px;
            font-weight: 800;
            line-height: 1;
            padding: 0 4px 0 0;
        }

        .ct-fin-anotacao-wrap {
            align-items: center;
            display: grid;
            gap: 4px;
            grid-template-columns: minmax(0, 1fr) 18px;
        }

        .ct-fin-anotacao-wrap .ct-fin-text-input {
            min-width: 0;
        }

        .ct-fin-replicar {
            align-items: center;
            background: transparent;
            border: 0;
            border-radius: 999px;
            cursor: pointer;
            display: inline-flex;
            height: 18px;
            justify-content: center;
            padding: 0;
            transition: color 120ms ease, opacity 120ms ease, transform 120ms ease;
            width: 18px;
        }

        .ct-fin-replicar svg {
            height: 16px;
            stroke-width: 3;
            width: 16px;
        }

        .ct-fin-replicar-on {
            color: #111827;
        }

        .ct-fin-replicar-off {
            color: #cbd5e1;
        }

        .ct-fin-replicar:hover {
            opacity: 0.85;
            transform: translateX(1px);
        }

        .ct-fin-th-action {
            align-items: center;
            display: inline-flex;
            gap: 5px;
            justify-content: center;
            width: 100%;
        }

        .ct-fin-copy-planned {
            align-items: center;
            background: transparent;
            border: 0;
            color: #4f63c6;
            cursor: pointer;
            display: inline-flex;
            height: 18px;
            justify-content: center;
            padding: 0;
            transition: color 120ms ease, opacity 120ms ease, transform 120ms ease;
            width: 18px;
        }

        .ct-fin-copy-planned svg {
            height: 16px;
            width: 16px;
        }

        .ct-fin-copy-planned:hover {
            color: #3346a8;
            transform: translateY(1px);
        }

        .ct-fin-copy-planned-active {
            color: #15803d;
        }

        .ct-fin-copy-planned:disabled {
            cursor: wait;
            opacity: 0.85;
            transform: none;
        }

        .ct-fin-spinner {
            animation: ct-fin-spin 700ms linear infinite;
            border: 1.5px solid currentColor;
            border-right-color: transparent;
            border-radius: 999px;
            display: inline-block;
            height: 12px;
            width: 12px;
        }

        @keyframes ct-fin-spin {
            to {
                transform: rotate(360deg);
            }
        }

        .ct-fin-modal-backdrop {
            align-items: stretch;
            background: rgba(15, 23, 42, 0.28);
            display: flex;
            inset: 0;
            justify-content: flex-end;
            position: fixed;
            z-index: 40;
        }

        .ct-fin-modal {
            background: #ffffff;
            border-radius: 8px 0 0 8px;
            box-shadow: -18px 0 60px rgba(15, 23, 42, 0.22);
            display: grid;
            gap: 18px;
            grid-template-rows: auto auto minmax(0, 1fr);
            height: 100vh;
            padding: 24px 28px;
            width: min(92vw, max(540px, 40vw));
        }

        .ct-fin-modal-head {
            align-items: start;
            display: flex;
            gap: 18px;
            justify-content: space-between;
        }

        .ct-fin-modal-kicker {
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .ct-fin-modal-title {
            color: #0f172a;
            font-size: 24px;
            font-weight: 750;
            line-height: 1.15;
            margin-top: 4px;
        }

        .ct-fin-modal-close {
            align-items: center;
            background: #ffffff;
            border: 1px solid #d9dee7;
            border-radius: 6px;
            color: #334155;
            cursor: pointer;
            display: inline-flex;
            flex: 0 0 auto;
            font-size: 24px;
            height: 38px;
            justify-content: center;
            line-height: 1;
            width: 38px;
        }

        .ct-fin-modal-body {
            min-height: 0;
            overflow-y: auto;
            padding-right: 4px;
        }

        .ct-fin-modal-tabs {
            border-bottom: 1px solid #cbd5e1;
            display: flex;
            gap: 12px;
        }

        .ct-fin-modal-tab {
            background: transparent;
            border: 0;
            border-bottom: 2px solid transparent;
            color: #475569;
            cursor: pointer;
            font-size: 15px;
            height: 36px;
            padding: 0 18px;
        }

        .ct-fin-modal-tab-active {
            border-bottom-color: var(--ct-primary);
            color: #0f172a;
        }

        .ct-fin-modal-card {
            display: grid;
            gap: 18px;
            padding: 4px 0 0;
        }

        .ct-fin-modal-client {
            color: #475569;
            font-size: 18px;
            font-weight: 700;
            line-height: 1.15;
        }

        .ct-fin-modal-grid {
            display: grid;
            gap: 18px 10px;
            grid-template-columns: 1fr 1fr;
        }

        .ct-fin-modal-field {
            display: grid;
            gap: 8px;
        }

        .ct-fin-modal-field-full {
            grid-column: 1 / -1;
        }

        .ct-fin-modal-label {
            color: #334155;
            font-size: 15px;
        }

        .ct-fin-modal-input,
        .ct-fin-modal-select {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            color: #0f172a;
            font-size: 15px;
            height: 50px;
            padding: 0 16px;
            width: 100%;
        }

        .ct-fin-modal-input:disabled,
        .ct-fin-modal-select:disabled {
            background: #eef2f7;
            color: #94a3b8;
        }

        .ct-fin-modal-actions {
            background: linear-gradient(to bottom, rgba(255, 255, 255, 0.88), #ffffff 18px);
            bottom: 0;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            padding-top: 18px;
            position: sticky;
            z-index: 2;
        }

        .ct-fin-modal-primary {
            background: var(--ct-primary);
            border-color: var(--ct-primary);
            color: #111827;
        }

        .ct-fin-modal-primary:disabled {
            background: #fef3c7;
            border-color: #fcd34d;
            color: #92400e;
            opacity: 0.65;
        }

        .ct-fin-modal-secondary {
            background: #ffffff;
            border-color: #d9dee7;
            color: #334155;
        }

        .ct-fin-modal-action-btn {
            min-width: 108px;
        }

        .ct-fin-modal-empty {
            align-items: center;
            color: #64748b;
            display: flex;
            min-height: 280px;
        }

        @media (max-width: 720px) {
            .ct-fin-modal {
                border-radius: 0;
                max-width: none;
                padding: 20px;
                width: 100vw;
            }

            .ct-fin-modal-grid,
            .ct-fin-parcel-form {
                grid-template-columns: 1fr;
            }
        }

        .ct-fin-parcel-form {
            align-items: end;
            display: grid;
            gap: 10px;
            grid-template-columns: 1fr 1fr auto;
            padding: 8px 0 34px;
        }

        .ct-fin-parcel-table {
            border: 1px solid #d9dee7;
            border-radius: 6px;
            overflow: hidden;
        }

        .ct-fin-parcel-total {
            font-size: 17px;
            font-weight: 800;
            padding-top: 12px;
            text-align: right;
        }

        .ct-fin-parcel-delete {
            background: transparent;
            border: 0;
            color: var(--ct-primary-strong);
            cursor: pointer;
            font-size: 14px;
        }

        .ct-fin-parcel-delete:disabled {
            color: #a8b1c1;
            cursor: not-allowed;
        }

        .ct-fin-boleto-title {
            color: #0f172a;
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .ct-fin-boleto-card {
            background: #ffffff;
            border: 1px solid #d9dee7;
            border-radius: 6px;
            display: grid;
            gap: 18px;
            padding: 24px;
        }

        .ct-fin-boleto-history {
            display: grid;
            gap: 8px;
            margin-bottom: 18px;
        }

        .ct-fin-boleto-history-title {
            margin-top: 18px;
        }

        .ct-fin-boleto-collapse {
            gap: 10px;
            padding: 0;
        }

        .ct-fin-boleto-collapse summary {
            align-items: center;
            cursor: pointer;
            display: grid;
            gap: 10px;
            grid-template-columns: 1fr auto auto;
            list-style-position: inside;
            padding: 14px 16px;
        }

        .ct-fin-boleto-collapse .ct-fin-boleto-detail {
            margin: 0 16px 16px;
        }

        .ct-fin-boleto-summary {
            display: grid;
            gap: 12px;
            grid-template-columns: 1fr 1fr;
        }

        .ct-fin-boleto-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            display: grid;
            gap: 8px;
            min-height: 86px;
            padding: 14px 16px;
        }

        .ct-fin-boleto-box-label {
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
        }

        .ct-fin-boleto-amount {
            color: #0f172a;
            font-size: 24px;
            font-weight: 800;
            line-height: 1.1;
        }

        .ct-fin-boleto-row {
            align-items: center;
            color: #334155;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            min-height: 26px;
        }

        .ct-fin-boleto-detail {
            align-items: start;
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            display: grid;
            gap: 12px;
            padding-top: 18px;
        }

        .ct-fin-boleto-label {
            color: #1f2937;
            font-weight: 800;
        }

        .ct-fin-boleto-value {
            color: #334155;
        }

        .ct-fin-boleto-vencimento {
            align-items: center;
            display: grid;
            gap: 10px;
            grid-template-columns: minmax(220px, 1fr) auto;
            width: min(100%, 430px);
        }

        .ct-fin-boleto-actions {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .ct-fin-boleto-link,
        .ct-fin-boleto-action {
            color: #b45309;
            cursor: pointer;
            text-decoration: none;
        }

        .ct-fin-boleto-link {
            background: transparent;
            border: 0;
            font: inherit;
            padding: 0;
        }

        .ct-fin-boleto-action {
            background: transparent;
            border: 0;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.2;
            padding: 0;
        }

        .ct-fin-boleto-action:disabled {
            color: #a8b1c1;
            cursor: not-allowed;
        }

        .ct-fin-boleto-action-primary {
            color: #b45309;
        }

        .ct-fin-boleto-status {
            border-radius: 999px;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            line-height: 1.05;
            padding: 7px 12px;
            text-align: center;
        }

        .ct-fin-boleto-status-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .ct-fin-boleto-status-success {
            background: #dcfce7;
            color: #166534;
        }

        .ct-fin-boleto-status-danger {
            background: #fee2e2;
            color: #b91c1c;
        }

        .ct-fin-boleto-status-orange {
            background: #ffedd5;
            color: #c2410c;
        }

        .ct-fin-boleto-status-neutral {
            background: #e5e7eb;
            color: #4b5563;
        }

        @media (max-width: 1100px) {
            .ct-fin-toolbar {
                grid-template-columns: 1fr 1fr;
            }

            .ct-fin-grid-wrap {
                overflow-x: auto;
            }

            .ct-fin-grid {
                grid-template-columns: 500px 455px 455px;
                min-width: 1410px;
            }
        }

        @media (max-width: 700px) {
            .ct-fin-toolbar {
                grid-template-columns: 1fr;
            }

            .ct-fin-boleto-summary,
            .ct-fin-boleto-vencimento {
                grid-template-columns: 1fr;
            }
        }
    </style>

    @php
        [$mes1, $mes2] = $this->mesesVisiveis();
        $linhas = $this->linhasFinanceiro();
    @endphp

    <div class="ct-fin-page">
        <div class="ct-fin-toolbar">
            <label class="ct-fin-field">
                <span class="ct-fin-label">Status</span>
                <select wire:model.live="statusClienteId" class="ct-fin-select">
                    <option value="">Todos</option>
                    @foreach ($this->statusClientes() as $status)
                        <option value="{{ $status->id }}">{{ $status->label }}</option>
                    @endforeach
                </select>
            </label>

            <label class="ct-fin-field">
                <span class="ct-fin-label">Clientes</span>
                <input
                    type="search"
                    wire:model.live.debounce.400ms="clienteSearch"
                    placeholder="Nome ou palavras chave"
                    class="ct-fin-input"
                />
            </label>

            <label class="ct-fin-field">
                <span class="ct-fin-label">Vencimento</span>
                <input
                    type="number"
                    min="1"
                    max="31"
                    wire:model.live.debounce.400ms="diaVencimento"
                    placeholder="Dia"
                    class="ct-fin-input"
                />
            </label>

            <label class="ct-fin-field">
                <span class="ct-fin-label">Linhas</span>
                <input
                    type="number"
                    min="1"
                    max="200"
                    wire:model.live.debounce.400ms="linhas"
                    class="ct-fin-input"
                />
            </label>

            <button type="button" wire:click="limparFiltros" class="ct-fin-btn">Limpar</button>
            <button type="button" wire:click="exportarCsv" class="ct-fin-btn ct-fin-btn-export"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M7.5 12 12 16.5m0 0 4.5-4.5M12 16.5V3" /></svg><span>Exportar</span></button>
            <div></div>
        </div>

        <div class="ct-fin-grid-wrap">
            <div class="ct-fin-grid">
                <div>
                    <div class="ct-fin-section-title">
                        <span>Clientes</span>
                        <span>
                            <button type="button" wire:click="mesAnterior" class="ct-fin-arrow" title="Mes anterior">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                    <path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                            <button type="button" wire:click="mesProximo" class="ct-fin-arrow" title="Proximo mes">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                    <path d="M9 18l6-6-6-6" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                        </span>
                    </div>
                    <table class="ct-fin-table">
                        <colgroup>
                            <col style="width: 60px" />
                            <col style="width: 118px" />
                            <col style="width: 128px" />
                            <col style="width: 78px" />
                            <col style="width: 116px" />
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="ct-fin-number">Qtd</th>
                                <th>Vendedor</th>
                                <th>Cliente</th>
                                <th>Venc.</th>
                                <th>Anotacoes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($linhas as $linha)
                                <tr wire:key="cliente-financeiro-{{ $linha['cliente']->id }}">
                                    <td class="ct-fin-number">{{ $linha['qtd'] }}</td>
                                    <td title="{{ $linha['vendedor'] }}">{{ $linha['vendedor'] }}</td>
                                    <td title="{{ $linha['cliente']->nome }}">{{ $linha['cliente']->nome }}</td>
                                    <td>
                                        <input
                                            type="number"
                                            min="1"
                                            max="31"
                                            wire:model.live.debounce.2000ms="vencimentos.{{ $linha['cliente']->id }}"
                                            wire:blur="salvarVencimento({{ $linha['cliente']->id }})"
                                            wire:keydown.enter="salvarVencimento({{ $linha['cliente']->id }})"
                                            class="ct-fin-text-input"
                                        />
                                    </td>
                                    <td>
                                        <div class="ct-fin-anotacao-wrap">
                                            <input
                                                type="text"
                                                wire:model.live.debounce.2000ms="anotacoes.{{ $linha['cliente']->id }}"
                                                wire:blur="salvarAnotacao({{ $linha['cliente']->id }})"
                                                wire:keydown.enter="salvarAnotacao({{ $linha['cliente']->id }})"
                                                class="ct-fin-text-input"
                                            />
                                            <button
                                                type="button"
                                                x-data="{ active: @js((bool) $linha['cliente']->replicar_pagamento) }"
                                                x-effect="active = @js((bool) $linha['cliente']->replicar_pagamento)"
                                                x-on:click="active = ! active; $wire.toggleReplicar({{ $linha['cliente']->id }})"
                                                class="ct-fin-replicar"
                                                x-bind:class="active ? 'ct-fin-replicar-on' : 'ct-fin-replicar-off'"
                                                title="{{ $linha['cliente']->replicar_pagamento ? 'Replicacao de pagamento ativa' : 'Replicacao de pagamento inativa' }}"
                                                aria-label="{{ $linha['cliente']->replicar_pagamento ? 'Desativar replicacao de pagamento' : 'Ativar replicacao de pagamento' }}"
                                            >
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                                    <path d="M9 18l6-6-6-6" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="ct-fin-number">Nenhum cliente encontrado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @foreach ([['meta' => $mes1, 'key' => 'mes1'], ['meta' => $mes2, 'key' => 'mes2']] as $month)
                    @php($totais = $this->totaisMes($month['meta']['mes'], $month['meta']['ano']))
                    @php($isMes1 = $month['key'] === 'mes1')
                    @php($boletoState = $isMes1 ? $numeroBoletoMes1 : $numeroBoletoMes2)
                    @php($efetuadoState = $isMes1 ? $valorEfetuadoMes1 : $valorEfetuadoMes2)
                    <div>
                        <div class="ct-fin-month-title">{{ $month['meta']['label'] }}</div>
                        <div class="ct-fin-month-filters">
                            <div class="ct-fin-filter-cell">
                                <button
                                    type="button"
                                    x-data="{ state: @js($boletoState), next() { this.state = this.state === 2 ? 1 : (this.state === 1 ? 0 : 2) } }"
                                    x-effect="state = @js($boletoState)"
                                    x-on:click="next(); $wire.{{ $isMes1 ? 'alternarNumeroBoletoMes1' : 'alternarNumeroBoletoMes2' }}()"
                                    class="ct-fin-tristate"
                                    x-bind:class="{ 'ct-fin-tristate-filled': state === 1, 'ct-fin-tristate-empty': state === 0 }"
                                    title="Filtro de boleto"
                                >
                                    <span x-show="state === 2" x-cloak>
                                        <svg class="ct-fin-fa-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" aria-hidden="true"><path fill="currentColor" d="M400 32H48C21.5 32 0 53.5 0 80v352c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V80c0-26.5-21.5-48-48-48zm0 400H48V80h352v352zM183 352.4 91.7 261.1c-4.7-4.7-4.7-12.3 0-17l22.6-22.6c4.7-4.7 12.3-4.7 17 0l60.3 60.3 124.7-124.7c4.7-4.7 12.3-4.7 17 0l22.6 22.6c4.7 4.7 4.7 12.3 0 17L200 352.4c-4.7 4.7-12.3 4.7-17 0z" /></svg>
                                    </span>
                                    <span x-show="state === 1" x-cloak>
                                        <svg class="ct-fin-fa-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" aria-hidden="true"><path fill="currentColor" d="M400 32H48C21.5 32 0 53.5 0 80v352c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V80c0-26.5-21.5-48-48-48zm0 400H48V80h352v352zM128 232h192c6.6 0 12 5.4 12 12v24c0 6.6-5.4 12-12 12H128c-6.6 0-12-5.4-12-12v-24c0-6.6 5.4-12 12-12z" /></svg>
                                    </span>
                                    <span x-show="state === 0" x-cloak>
                                        <svg class="ct-fin-fa-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" aria-hidden="true"><path fill="currentColor" d="M400 32H48C21.5 32 0 53.5 0 80v352c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V80c0-26.5-21.5-48-48-48zm0 400H48V80h352v352z" /></svg>
                                    </span>
                                </button>
                            </div>
                            <div class="ct-fin-filter-cell"></div>
                            <div class="ct-fin-filter-cell">
                                <button
                                    type="button"
                                    x-data="{ state: @js($efetuadoState), next() { this.state = this.state === 2 ? 1 : (this.state === 1 ? 0 : 2) } }"
                                    x-effect="state = @js($efetuadoState)"
                                    x-on:click="next(); $wire.{{ $isMes1 ? 'alternarValorEfetuadoMes1' : 'alternarValorEfetuadoMes2' }}()"
                                    class="ct-fin-tristate"
                                    x-bind:class="{ 'ct-fin-tristate-filled': state === 1, 'ct-fin-tristate-empty': state === 0 }"
                                    title="Filtro de valor efetuado"
                                >
                                    <span x-show="state === 2" x-cloak>
                                        <svg class="ct-fin-fa-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" aria-hidden="true"><path fill="currentColor" d="M400 32H48C21.5 32 0 53.5 0 80v352c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V80c0-26.5-21.5-48-48-48zm0 400H48V80h352v352zM183 352.4 91.7 261.1c-4.7-4.7-4.7-12.3 0-17l22.6-22.6c4.7-4.7 12.3-4.7 17 0l60.3 60.3 124.7-124.7c4.7-4.7 12.3-4.7 17 0l22.6 22.6c4.7 4.7 4.7 12.3 0 17L200 352.4c-4.7 4.7-12.3 4.7-17 0z" /></svg>
                                    </span>
                                    <span x-show="state === 1" x-cloak>
                                        <svg class="ct-fin-fa-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" aria-hidden="true"><path fill="currentColor" d="M400 32H48C21.5 32 0 53.5 0 80v352c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V80c0-26.5-21.5-48-48-48zm0 400H48V80h352v352zM128 232h192c6.6 0 12 5.4 12 12v24c0 6.6-5.4 12-12 12H128c-6.6 0-12-5.4-12-12v-24c0-6.6 5.4-12 12-12z" /></svg>
                                    </span>
                                    <span x-show="state === 0" x-cloak>
                                        <svg class="ct-fin-fa-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" aria-hidden="true"><path fill="currentColor" d="M400 32H48C21.5 32 0 53.5 0 80v352c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V80c0-26.5-21.5-48-48-48zm0 400H48V80h352v352z" /></svg>
                                    </span>
                                </button>
                            </div>
                            <div class="ct-fin-filter-cell">
                                <input
                                    type="search"
                                    wire:model.live.debounce.400ms="{{ $isMes1 ? 'consultaMes1' : 'consultaMes2' }}"
                                    placeholder="Pesquisar"
                                    class="ct-fin-filter-input"
                                />
                            </div>
                        </div>
                        <table class="ct-fin-table">
                            <colgroup>
                                <col style="width: 21%" />
                                <col style="width: 24%" />
                                <col style="width: 22%" />
                                <col style="width: 33%" />
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>N. Boleto</th>
                                    <th>
                                        <span class="ct-fin-th-action">
                                            <span>Planejado</span>
                                            <button
                                                type="button"
                                                x-data="{ active: false }"
                                                x-on:click="if (active) return; active = true; Promise.resolve($wire.replicarPlanejadoMes({{ $month['meta']['mes'] }}, {{ $month['meta']['ano'] }})).finally(() => active = false)"
                                                class="ct-fin-copy-planned"
                                                x-bind:class="{ 'ct-fin-copy-planned-active': active }"
                                                x-bind:disabled="active"
                                                title="Replicar planejado do mes anterior"
                                                aria-label="Replicar planejado do mes anterior"
                                            >
                                                <span x-show="! active" x-cloak>
                                                    <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-11.25a.75.75 0 0 0-1.5 0v4.69L7.53 9.72a.75.75 0 0 0-1.06 1.06l3 3a.75.75 0 0 0 1.06 0l3-3a.75.75 0 1 0-1.06-1.06l-1.72 1.72V6.75Z" clip-rule="evenodd" />
                                                    </svg>
                                                </span>
                                                <span x-show="active" x-cloak>
                                                    <span class="ct-fin-spinner" aria-hidden="true"></span>
                                                </span>
                                            </button>
                                        </span>
                                    </th>
                                    <th>Efetuado</th>
                                    <th>Observacao</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($linhas as $linha)
                                    @php($lancamento = $linha[$month['key']])
                                    <tr wire:key="{{ $month['key'] }}-{{ $linha['cliente']->id }}">
                                        <td>
                                            <button
                                                type="button"
                                                wire:click="abrirLancamento({{ $linha['cliente']->id }}, {{ $month['meta']['mes'] }}, {{ $month['meta']['ano'] }})"
                                                class="ct-fin-pencil"
                                                title="Abrir lancamento"
                                            >
                                                &#9998;
                                            </button>
                                            {{ $lancamento->numero_boleto ?? '' }}
                                        </td>
                                        <td class="ct-fin-number">{{ $this->moeda($lancamento->valor_planejado ?? null) }}</td>
                                        <td class="ct-fin-number">{{ $this->moeda($lancamento->valor_efetivado ?? null) }}</td>
                                        <td title="{{ $lancamento->observacao ?? '' }}">{{ $lancamento->observacao ?? '' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="ct-fin-number">Nenhum cliente encontrado.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="ct-fin-total-row">
                            <div class="ct-fin-total-label">Total</div>
                            <div class="ct-fin-total-planejado">R${{ $this->moeda($totais['planejado']) }}</div>
                            <div class="ct-fin-total-efetivado">R${{ $this->moeda($totais['efetivado']) }}</div>
                            <div></div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="ct-fin-pagination">
                <div>
                    {{ $this->inicioPagina() }} a {{ $this->fimPagina() }} de {{ $this->totalClientes() }} registros
                </div>

                <div class="ct-fin-page-buttons">
                    <button
                        type="button"
                        wire:click="paginaAnterior"
                        class="ct-fin-page-btn"
                        @disabled($this->pagina <= 1)
                    >
                        &lt;
                    </button>

                    @php($ultimaPaginaRenderizada = null)
                    @foreach ($this->paginasVisiveis() as $pagina)
                        @if ($ultimaPaginaRenderizada !== null && $pagina > $ultimaPaginaRenderizada + 1)
                            <span class="ct-fin-page-gap">...</span>
                        @endif

                        <button
                            type="button"
                            wire:click="irParaPagina({{ $pagina }})"
                            class="ct-fin-page-btn {{ $pagina === $this->pagina ? 'ct-fin-page-btn-active' : '' }}"
                        >
                            {{ $pagina }}
                        </button>

                        @php($ultimaPaginaRenderizada = $pagina)
                    @endforeach

                    <button
                        type="button"
                        wire:click="paginaProxima"
                        class="ct-fin-page-btn"
                        @disabled($this->pagina >= $this->totalPaginas())
                    >
                        &gt;
                    </button>
                </div>

                <div></div>
            </div>
        </div>

        @if ($lancamentoModalAberto)
            <div class="ct-fin-modal-backdrop">
                <div class="ct-fin-modal">
                    <div class="ct-fin-modal-head">
                        <div>
                            <div class="ct-fin-modal-kicker">{{ $modalMes ? $this->mesNome($modalMes) : '' }} {{ $modalAno }}</div>
                            <div class="ct-fin-modal-title">{{ $modalClienteNome }}</div>
                        </div>
                        <button type="button" wire:click="fecharLancamento" class="ct-fin-modal-close" aria-label="Fechar">&times;</button>
                    </div>

                    <div class="ct-fin-modal-tabs">
                        <button
                            type="button"
                            wire:click="selecionarAbaModal('lancamento')"
                            wire:loading.attr="disabled"
                            wire:target="selecionarAbaModal"
                            class="ct-fin-modal-tab {{ $modalAba === 'lancamento' ? 'ct-fin-modal-tab-active' : '' }}"
                        >
                            Lancamento
                        </button>
                        <button
                            type="button"
                            wire:click="selecionarAbaModal('parcelamento')"
                            wire:loading.attr="disabled"
                            wire:target="selecionarAbaModal"
                            class="ct-fin-modal-tab {{ $modalAba === 'parcelamento' ? 'ct-fin-modal-tab-active' : '' }}"
                        >
                            Parcelamento
                        </button>
                        <button
                            type="button"
                            wire:click="selecionarAbaModal('boleto')"
                            wire:loading.attr="disabled"
                            wire:target="selecionarAbaModal"
                            class="ct-fin-modal-tab {{ $modalAba === 'boleto' ? 'ct-fin-modal-tab-active' : '' }}"
                        >
                            Boleto
                        </button>
                    </div>

                    <div class="ct-fin-modal-body">
                    <div class="ct-fin-modal-card">
                        @if ($modalAba === 'lancamento')
                            <div class="ct-fin-modal-client">{{ $modalClienteNome }}</div>

                            <div class="ct-fin-modal-grid">
                                <label class="ct-fin-modal-field">
                                    <span class="ct-fin-modal-label">Data Lancamento</span>
                                    <input
                                        type="date"
                                        wire:model="modalDataLancamento"
                                        class="ct-fin-modal-input"
                                    />
                                    @error('modalDataLancamento') <span class="ct-error">{{ $message }}</span> @enderror
                                </label>

                                <label class="ct-fin-modal-field">
                                    <span class="ct-fin-modal-label">Numero Boleto</span>
                                    <input
                                        type="text"
                                        wire:model="modalNumeroBoleto"
                                        class="ct-fin-modal-input"
                                    />
                                    @error('modalNumeroBoleto') <span class="ct-error">{{ $message }}</span> @enderror
                                </label>

                                <label class="ct-fin-modal-field">
                                    <span class="ct-fin-modal-label">Ano Referencia</span>
                                    <select class="ct-fin-modal-select" disabled>
                                        <option>{{ $modalAno }}</option>
                                    </select>
                                </label>

                                <label class="ct-fin-modal-field">
                                    <span class="ct-fin-modal-label">Mes Referencia</span>
                                    <select class="ct-fin-modal-select" disabled>
                                        <option>{{ $this->mesNome($modalMes) }}</option>
                                    </select>
                                </label>

                                <label class="ct-fin-modal-field">
                                    <span class="ct-fin-modal-label">Valor Planejado</span>
                                    <input
                                        type="text"
                                        inputmode="decimal"
                                        autocomplete="off"
                                        wire:model.blur="modalValorPlanejado"
                                        oninput="this.value = window.conecttaMaskMoney(this.value)"
                                        class="ct-fin-modal-input"
                                    />
                                    @error('modalValorPlanejado') <span class="ct-error">{{ $message }}</span> @enderror
                                </label>

                                <label class="ct-fin-modal-field">
                                    <span class="ct-fin-modal-label">Valor Efetivado</span>
                                    <input
                                        type="text"
                                        inputmode="decimal"
                                        autocomplete="off"
                                        wire:model.blur="modalValorEfetivado"
                                        oninput="this.value = window.conecttaMaskMoney(this.value)"
                                        class="ct-fin-modal-input"
                                    />
                                    @error('modalValorEfetivado') <span class="ct-error">{{ $message }}</span> @enderror
                                </label>

                                <label class="ct-fin-modal-field ct-fin-modal-field-full">
                                    <span class="ct-fin-modal-label">Observacao</span>
                                    <input
                                        type="text"
                                        wire:model="modalObservacao"
                                        class="ct-fin-modal-input"
                                    />
                                    @error('modalObservacao') <span class="ct-error">{{ $message }}</span> @enderror
                                </label>
                            </div>

                            <div class="ct-fin-modal-actions">
                                <button type="button" wire:click="fecharLancamento" class="ct-fin-btn ct-fin-modal-secondary ct-fin-modal-action-btn">Fechar</button>
                                <button type="button" wire:click="salvarLancamentoModal" class="ct-fin-btn ct-fin-modal-primary ct-fin-modal-action-btn">Salvar</button>
                            </div>
                        @elseif ($modalAba === 'parcelamento')
                            <div>
                                <div class="ct-fin-parcel-form">
                                    <label class="ct-fin-modal-field">
                                        <span class="ct-fin-modal-label">Data Lancamento</span>
                                        <input
                                            type="date"
                                            wire:model="parcelamentoDataLancamento"
                                            class="ct-fin-modal-input"
                                        />
                                        @error('parcelamentoDataLancamento') <span class="ct-error">{{ $message }}</span> @enderror
                                    </label>

                                    <label class="ct-fin-modal-field">
                                        <span class="ct-fin-modal-label">Valor Efetivado</span>
                                        <input
                                            type="text"
                                            inputmode="decimal"
                                            autocomplete="off"
                                            wire:model.blur="parcelamentoValorEfetivado"
                                            oninput="this.value = window.conecttaMaskMoney(this.value)"
                                            class="ct-fin-modal-input"
                                        />
                                        @error('parcelamentoValorEfetivado') <span class="ct-error">{{ $message }}</span> @enderror
                                    </label>

                                    <button
                                        type="button"
                                        wire:click="lancarParcelamento"
                                        class="ct-fin-btn ct-fin-modal-primary ct-fin-modal-action-btn"
                                        @disabled(! $this->podeLancarParcelamento())
                                    >
                                        Lan&ccedil;ar
                                    </button>
                                </div>

                                <div class="ct-fin-parcel-table">
                                    <table class="ct-fin-table">
                                        <colgroup>
                                            <col style="width: 48%" />
                                            <col style="width: 25%" />
                                            <col style="width: 27%" />
                                        </colgroup>
                                        <thead>
                                            <tr>
                                                <th>Data Lancamento</th>
                                                <th>Valor</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($this->parcelamentosModal() as $parcelamento)
                                                <tr wire:key="parcelamento-{{ $parcelamento->id }}">
                                                    <td>{{ $parcelamento->data_lancamento?->format('Y-m-d') }}</td>
                                                    <td>{{ $this->moeda($parcelamento->valor_efetivado) }}</td>
                                                    <td class="ct-fin-number">
                                                        <button
                                                            type="button"
                                                            wire:click="excluirParcelamento({{ $parcelamento->id }})"
                                                            class="ct-fin-parcel-delete"
                                                            @disabled($parcelamento->id === $modalLancamentoId)
                                                        >
                                                            Excluir
                                                        </button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="ct-fin-number">Nenhum parcelamento encontrado.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <div class="ct-fin-parcel-total">Total: {{ $this->moeda($this->totalParcelamentosModal()) }}</div>

                                <div class="ct-fin-modal-actions">
                                    <button type="button" wire:click="fecharLancamento" class="ct-fin-btn ct-fin-modal-secondary ct-fin-modal-action-btn">Fechar</button>
                                </div>
                            </div>
                        @else
                            @php($boleto = $this->boletoModal())
                            @php($boletosHistorico = $this->boletosModal()->filter(fn ($item) => ! $boleto || $item->id !== $boleto->id))
                            <div>
                                <div class="ct-fin-boleto-title">{{ $boleto ? 'Boleto' : 'Novo Boleto' }}</div>
                                <div class="ct-fin-boleto-card">
                                    <div class="ct-fin-boleto-summary">
                                        <div class="ct-fin-boleto-box">
                                            <span class="ct-fin-boleto-box-label">Valor do boleto</span>
                                            <span class="ct-fin-boleto-amount">{{ $this->boletoValor() }}</span>
                                        </div>

                                        <div class="ct-fin-boleto-box">
                                            <span class="ct-fin-boleto-box-label">Status</span>
                                            <span class="ct-fin-boleto-status {{ $this->boletoStatusClasse() }}">
                                                {{ $this->boletoStatus() }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="ct-fin-boleto-detail">
                                        <div class="ct-fin-boleto-row">
                                            <span class="ct-fin-boleto-label">Vencimento:</span>
                                            @if ($boleto)
                                                <span class="ct-fin-boleto-value">{{ $this->boletoVencimentoExibicao() }}</span>
                                            @else
                                                <div class="ct-fin-boleto-vencimento">
                                                    <input
                                                        type="date"
                                                        wire:model="boletoVencimento"
                                                        class="ct-fin-modal-input"
                                                    />
                                                    <span class="ct-fin-boleto-value">{{ $this->boletoVencimentoExibicao() }}</span>
                                                </div>
                                            @endif
                                        </div>

                                        @if ($boleto)
                                            <div class="ct-fin-boleto-row">
                                                <span class="ct-fin-boleto-label">Links:</span>
                                                @if ($this->boletoInvoiceUrl())
                                                    <a href="{{ $this->boletoInvoiceUrl() }}" target="_blank" rel="noopener noreferrer" class="ct-fin-boleto-link">Invoice</a>
                                                @endif
                                                @if ($this->boletoPrintUrl())
                                                    <a href="{{ $this->boletoPrintUrl() }}" target="_blank" rel="noopener noreferrer" class="ct-fin-boleto-link">Boleto</a>
                                                @endif
                                            </div>
                                        @endif

                                        <div class="ct-fin-boleto-row">
                                            <span class="ct-fin-boleto-label">Acoes:</span>
                                            <div class="ct-fin-boleto-actions">
                                                @if ($boleto)
                                                    {{-- Botao mantido oculto ate o fluxo de baixa ser definido.
                                                    <button
                                                        type="button"
                                                        wire:click="realizarBaixaBoleto"
                                                        class="ct-fin-boleto-action ct-fin-boleto-action-primary"
                                                        @disabled(! $this->boletoPodeRealizarBaixa($boleto))
                                                    >
                                                        Realizar baixa
                                                    </button>
                                                    --}}
                                                    <button
                                                        type="button"
                                                        wire:click="mountAction('confirmarCancelamentoBoleto')"
                                                        class="ct-fin-boleto-action"
                                                    >
                                                        Cancelar Boleto
                                                    </button>
                                                @else
                                                    <button type="button" wire:click="gerarBoleto" class="ct-fin-boleto-action ct-fin-boleto-action-primary">Gerar Boleto</button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @if ($boletosHistorico->isNotEmpty())
                                    <div class="ct-fin-boleto-title ct-fin-boleto-history-title">Boletos anteriores</div>
                                    <div class="ct-fin-boleto-history">
                                        @foreach ($boletosHistorico as $boletoHistorico)
                                            <details class="ct-fin-boleto-card ct-fin-boleto-collapse" wire:key="boleto-historico-{{ $boletoHistorico->id }}">
                                                <summary>
                                                    <span>{{ $boletoHistorico->vencimento?->format('d/m/Y') ?? 'Sem vencimento' }}</span>
                                                    <span class="ct-fin-boleto-status {{ $this->boletoStatusClasse($boletoHistorico->status) }}">
                                                        {{ $this->statusBoletoLabel($boletoHistorico->status) ?: 'Sem status' }}
                                                    </span>
                                                    <span>{{ $this->moeda($boletoHistorico->total_value ?? 0) }}</span>
                                                </summary>

                                                <div class="ct-fin-boleto-detail">
                                                    <div class="ct-fin-boleto-row">
                                                        <span class="ct-fin-boleto-label">Fatura:</span>
                                                        <span class="ct-fin-boleto-value">{{ $boletoHistorico->fatura_id }}</span>
                                                    </div>

                                                    <div class="ct-fin-boleto-row">
                                                        <span class="ct-fin-boleto-label">Links:</span>
                                                        @if ($this->boletoInvoiceUrl($boletoHistorico))
                                                            <a href="{{ $this->boletoInvoiceUrl($boletoHistorico) }}" target="_blank" rel="noopener noreferrer" class="ct-fin-boleto-link">Invoice</a>
                                                        @endif
                                                        @if ($this->boletoPrintUrl($boletoHistorico))
                                                            <a href="{{ $this->boletoPrintUrl($boletoHistorico) }}" target="_blank" rel="noopener noreferrer" class="ct-fin-boleto-link">Boleto</a>
                                                        @endif
                                                    </div>
                                                </div>
                                            </details>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="ct-fin-modal-actions">
                                    <button type="button" wire:click="fecharLancamento" class="ct-fin-btn ct-fin-modal-secondary ct-fin-modal-action-btn">Fechar</button>
                                </div>
                            </div>
                        @endif
                    </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <script>
        window.conecttaMaskMoney = window.conecttaMaskMoney || function (value) {
            const digits = String(value || '').replace(/\D/g, '');

            if (digits === '') {
                return '';
            }

            return (Number.parseInt(digits, 10) / 100).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        };
    </script>
</x-filament-panels::page>
