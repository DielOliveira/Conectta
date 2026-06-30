<x-filament-panels::page>
    <style>
        .ct-invoice-page {
            --ct-primary: #f59e0b;
            display: grid;
            gap: 16px;
            width: 100%;
        }

        .ct-invoice-top-actions {
            display: flex;
            justify-content: flex-end;
        }

        .ct-invoice-filterbar {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            display: grid;
            align-items: end;
            gap: 12px;
            grid-template-columns: 170px 170px 190px minmax(260px, 1fr) 100px;
            overflow-x: auto;
            padding: 16px 24px;
        }

        .ct-invoice-field {
            display: grid;
            gap: 7px;
        }

        .ct-invoice-label {
            color: #111827;
            font-size: 13px;
            font-weight: 600;
        }

        .ct-invoice-input,
        .ct-invoice-select {
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

        .ct-invoice-input:focus,
        .ct-invoice-select:focus {
            border-color: var(--ct-primary);
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.18);
        }

        .ct-invoice-actions {
            align-items: end;
            display: flex;
        }

        .ct-invoice-btn {
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

        .ct-invoice-top-actions .ct-invoice-btn {
            width: auto;
        }

        .ct-invoice-btn:hover {
            background: #f9fafb;
        }

        .ct-invoice-btn svg {
            color: #9ca3af;
            height: 18px;
            width: 18px;
        }

        .ct-invoice-table-wrap {
            background: #ffffff;
            border: 1px solid #d9dee7;
            border-radius: 8px;
            overflow: hidden;
        }

        .ct-invoice-table {
            border-collapse: collapse;
            table-layout: fixed;
            width: 100%;
        }

        .ct-invoice-table th {
            background: #f8fafc;
            border-bottom: 1px solid #d9dee7;
            color: #1f2937;
            font-size: 13px;
            font-weight: 800;
            height: 48px;
            padding: 0 14px;
            text-align: left;
        }

        .ct-invoice-table td {
            border-bottom: 1px solid #d9dee7;
            color: #0f172a;
            font-size: 13px;
            height: 58px;
            overflow: hidden;
            padding: 0 14px;
            text-overflow: ellipsis;
            vertical-align: middle;
            white-space: nowrap;
        }

        .ct-invoice-table .ct-wrap {
            line-height: 1.35;
            white-space: normal;
        }

        .ct-invoice-link {
            color: #b45309;
            font-weight: 600;
            text-decoration: none;
        }

        .ct-invoice-link-separator {
            color: #94a3b8;
            padding: 0 3px;
        }

        .ct-invoice-status-cell {
            overflow: visible !important;
            white-space: normal !important;
        }

        .ct-invoice-badge {
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

        .ct-invoice-badge-warning { background: #fef3c7; color: #92400e; }
        .ct-invoice-badge-success { background: #dcfce7; color: #166534; }
        .ct-invoice-badge-danger { background: #fee2e2; color: #b91c1c; }
        .ct-invoice-badge-orange { background: #ffedd5; color: #c2410c; }
        .ct-invoice-badge-neutral { background: #e2e8f0; color: #334155; }

        .ct-invoice-empty {
            color: #64748b;
            padding: 26px;
            text-align: center;
        }

        .ct-invoice-pagination {
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

        .ct-invoice-page-size-wrap {
            align-items: center;
            border: 1px solid #d9dee7;
            border-radius: 8px;
            display: inline-flex;
            justify-self: center;
            overflow: hidden;
        }

        .ct-invoice-page-size-label {
            background: #ffffff;
            border-right: 1px solid #d9dee7;
            color: #6b7280;
            height: 36px;
            line-height: 36px;
            padding: 0 14px;
            white-space: nowrap;
        }

        .ct-invoice-page-size {
            border: 0;
            border-radius: 0;
            height: 36px;
            padding: 0 34px 0 12px;
            width: 78px;
        }

        .ct-invoice-page-buttons {
            align-items: center;
            border: 1px solid #d9dee7;
            border-radius: 8px;
            display: inline-flex;
            justify-self: end;
            overflow: hidden;
        }

        .ct-invoice-page-btn {
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

        .ct-invoice-page-btn:last-child {
            border-right: 0;
        }

        .ct-invoice-page-btn-active {
            color: #b45309;
            font-weight: 800;
        }

        .ct-invoice-page-btn:disabled {
            background: #f8fafc;
            color: #cbd5e1;
            cursor: not-allowed;
        }

        @media (max-width: 850px) {
            .ct-invoice-filterbar,
            .ct-invoice-pagination {
                grid-template-columns: 1fr;
            }

            .ct-invoice-page-buttons,
            .ct-invoice-page-size-wrap {
                justify-self: start;
            }
        }
    </style>

    @php($total = $this->totalInvoices())
    @php($totalPaginas = $this->totalPaginas())
    @php($inicio = $this->inicioPagina())
    @php($fim = $this->fimPagina())

    <div class="ct-invoice-page">
        <div class="ct-invoice-top-actions">
            <button type="button" wire:click="exportarCsv" class="ct-invoice-btn">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M7.5 12 12 16.5m0 0 4.5-4.5M12 16.5V3" /></svg>
                <span>Exportar</span>
            </button>
        </div>

        <div class="ct-invoice-filterbar">
            <label class="ct-invoice-field">
                <span class="ct-invoice-label">Criado em In&iacute;cio</span>
                <input type="date" wire:model.live="criadoInicio" class="ct-invoice-input" />
            </label>

            <label class="ct-invoice-field">
                <span class="ct-invoice-label">Criado em Fim</span>
                <input type="date" wire:model.live="criadoFim" class="ct-invoice-input" />
            </label>

            <label class="ct-invoice-field">
                <span class="ct-invoice-label">Status</span>
                <select wire:model.live="status" class="ct-invoice-select">
                    <option value="">Todos</option>
                    @foreach ($this->statusBoletos() as $status)
                        <option value="{{ $status['value'] }}">{{ $status['label'] }}</option>
                    @endforeach
                </select>
            </label>

            <label class="ct-invoice-field">
                <span class="ct-invoice-label">Pesquisa</span>
                <input type="search" wire:model.live.debounce.500ms="pesquisa" placeholder="Pesquisar" class="ct-invoice-input" />
            </label>

            <div class="ct-invoice-actions">
                <button type="button" wire:click="limparFiltros" class="ct-invoice-btn">Limpar</button>
            </div>
        </div>

        <div class="ct-invoice-table-wrap">
            <table class="ct-invoice-table">
                <colgroup>
                    <col style="width: 19%" />
                    <col style="width: 12%" />
                    <col style="width: 9%" />
                    <col style="width: 10%" />
                    <col style="width: 15%" />
                    <col style="width: 16%" />
                    <col style="width: 9%" />
                    <col style="width: 10%" />
                </colgroup>
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>CPF/CNPJ</th>
                        <th>Refer&ecirc;ncia</th>
                        <th>Vencimento</th>
                        <th>Invoice</th>
                        <th>Status</th>
                        <th>Valor</th>
                        <th>Data Gerado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $invoices = $this->invoices(); ?>

                    <?php if ($invoices->isEmpty()): ?>
                        <tr>
                            <td colspan="8" class="ct-invoice-empty">Nenhum boleto encontrado.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($invoices as $invoice): ?>
                            <?php
                                $lancamento = $invoice->lancamento;
                                $cliente = $lancamento?->cliente;
                                $invoiceUrl = $this->invoiceUrl($invoice);
                                $boletoUrl = $this->boletoUrl($invoice);
                            ?>
                            <tr>
                                <td class="ct-wrap" title="<?php echo e($cliente?->nome); ?>"><?php echo e($cliente?->nome); ?></td>
                                <td><?php echo e($cliente?->cpf_cnpj ?? $invoice->cpf_cnpj); ?></td>
                                <td><?php echo e($this->referencia($lancamento?->mes_referencia, $lancamento?->ano_referencia)); ?></td>
                                <td><?php echo e($this->data($invoice->vencimento)); ?></td>
                                <td>
                                    <?php if ($invoiceUrl): ?>
                                        <a href="<?php echo e($invoiceUrl); ?>" target="_blank" rel="noopener noreferrer" class="ct-invoice-link">Invoice</a>
                                    <?php else: ?>
                                        <span class="ct-invoice-link">Invoice</span>
                                    <?php endif; ?>
                                    <?php if ($boletoUrl): ?>
                                        <span class="ct-invoice-link-separator">/</span>
                                        <a href="<?php echo e($boletoUrl); ?>" target="_blank" rel="noopener noreferrer" class="ct-invoice-link">Boleto</a>
                                    <?php else: ?>
                                        <span class="ct-invoice-link-separator">/</span>
                                        <span class="ct-invoice-link">Boleto</span>
                                    <?php endif; ?>
                                </td>
                                <td class="ct-invoice-status-cell">
                                    <?php if (filled($invoice->status)): ?>
                                        <span class="ct-invoice-badge <?php echo e($this->statusBoletoClasse($invoice->status)); ?>">
                                            <?php echo e($this->statusBoletoLabel($invoice->status)); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($this->moeda($invoice->total_value)); ?></td>
                                <td><?php echo e($this->data($invoice->created_at)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="ct-invoice-pagination">
            <div>Exibindo {{ $inicio }} a {{ $fim }} de {{ number_format($total, 0, ',', '.') }} resultados</div>

            <div class="ct-invoice-page-size-wrap">
                <span class="ct-invoice-page-size-label">por p&aacute;gina</span>
                <select wire:model.live="porPagina" class="ct-invoice-select ct-invoice-page-size">
                    <option value="7">7</option>
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>

            <div class="ct-invoice-page-buttons">
                <button type="button" wire:click="paginaAnterior" class="ct-invoice-page-btn" @disabled($this->pagina <= 1)>&lt;</button>

                @foreach ($this->paginasVisiveis() as $pagina)
                    @if ($pagina === '...')
                        <span class="ct-invoice-page-btn">...</span>
                    @else
                        <button
                            type="button"
                            wire:click="irParaPagina({{ $pagina }})"
                            class="ct-invoice-page-btn {{ $pagina === $this->pagina ? 'ct-invoice-page-btn-active' : '' }}"
                        >
                            {{ $pagina }}
                        </button>
                    @endif
                @endforeach

                <button type="button" wire:click="paginaProxima" class="ct-invoice-page-btn" @disabled($this->pagina >= $totalPaginas)>&gt;</button>
            </div>
        </div>
    </div>
</x-filament-panels::page>



