<x-filament-panels::page>
    <style>
        .ct-restore-page {
            --ct-primary: #f59e0b;
            --ct-primary-strong: #d97706;
            --ct-primary-soft: rgba(245, 158, 11, 0.18);
            display: grid;
            gap: 18px;
            max-width: 1180px;
        }

        .ct-restore-card {
            background: #ffffff;
            border: 1px solid #d9dee7;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            padding: 22px;
        }

        .ct-restore-title {
            color: #0f172a;
            font-size: 22px;
            font-weight: 800;
            margin: 0;
        }

        .ct-restore-subtitle {
            color: #64748b;
            font-size: 14px;
            line-height: 1.45;
            margin: 6px 0 0;
            max-width: 940px;
        }

        .ct-restore-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .ct-upload-card {
            background: #ffffff;
            border: 1px solid #d9dee7;
            border-radius: 8px;
            display: grid;
            gap: 10px;
            padding: 16px;
        }

        .ct-upload-label {
            color: #334155;
            font-size: 14px;
            font-weight: 700;
        }

        .ct-file-input {
            color: #334155;
            font-size: 13px;
            max-width: 100%;
        }

        .ct-file-input::file-selector-button {
            background: #ffffff;
            border: 1px solid var(--ct-primary);
            border-radius: 6px;
            color: #b45309;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            margin-right: 10px;
            padding: 8px 12px;
        }

        .ct-file-input::file-selector-button:hover {
            background: #fffbeb;
        }

        .ct-warning {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 8px;
            color: #713f12;
            padding: 16px;
        }

        .ct-checkbox-line {
            align-items: flex-start;
            display: flex;
            gap: 10px;
            font-size: 14px;
            line-height: 1.45;
        }

        .ct-checkbox-line input {
            margin-top: 3px;
        }

        .ct-actions {
            align-items: center;
            display: flex;
            gap: 12px;
        }

        .ct-btn {
            align-items: center;
            background: #ffffff;
            border: 1px solid #d9dee7;
            border-radius: 8px;
            color: #0f172a;
            cursor: pointer;
            display: inline-flex;
            font-size: 14px;
            font-weight: 800;
            height: 44px;
            justify-content: center;
            padding: 0 18px;
        }

        .ct-btn-primary {
            background: var(--ct-primary);
            border-color: var(--ct-primary);
            color: #111827;
        }

        .ct-btn[disabled] {
            cursor: wait;
            opacity: 0.65;
        }

        .ct-error {
            color: #dc2626;
            font-size: 12px;
        }

        .ct-summary-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin-top: 14px;
        }

        .ct-summary-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            color: #334155;
            font-size: 14px;
            padding: 10px 12px;
        }

        .ct-summary-item strong {
            color: #0f172a;
            display: block;
            font-size: 13px;
            margin-bottom: 2px;
        }

        .ct-alert-list {
            margin: 10px 0 0 18px;
            padding: 0;
        }

        .ct-alert-list li {
            margin-top: 4px;
        }

        @media (max-width: 1100px) {
            .ct-restore-grid,
            .ct-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 680px) {
            .ct-restore-grid,
            .ct-summary-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="ct-restore-page">
        <section class="ct-restore-card">
            <h2 class="ct-restore-title">Restore operacional por arquivos XLSX</h2>
            <p class="ct-restore-subtitle">
                Esta rotina limpa os dados operacionais e importa os dumps preservando os IDs originais. Usuarios, permissoes e configuracoes de integracao nao sao apagados.
            </p>
        </section>

        <form wire:submit.prevent="restaurar" class="ct-restore-page">
            <section class="ct-restore-card">
                <div class="ct-restore-grid">
                    @foreach ([
                        'clientes' => 'Cliente.xlsx',
                        'veiculos' => 'Veiculos.xlsx',
                        'rastreadores' => 'Rastreador.xlsx',
                        'tecnicos' => 'Tecnico.xlsx',
                        'vendedores' => 'Vendedores.xlsx',
                        'lancamentos' => 'Lancamento.xlsx',
                        'invoices' => 'Invoice.xlsx',
                        'faturamentos' => 'Faturamento.xlsx',
                    ] as $campo => $label)
                        <label class="ct-upload-card">
                            <span class="ct-upload-label">{{ $label }}</span>
                            <input class="ct-file-input" type="file" wire:model="{{ $campo }}" accept=".xlsx">
                            @error($campo) <span class="ct-error">{{ $message }}</span> @enderror
                        </label>
                    @endforeach
                </div>
            </section>

            <section class="ct-warning">
                <label class="ct-checkbox-line">
                    <input type="checkbox" wire:model="confirmarLimpeza">
                    <span>Confirmo que a base operacional sera limpa antes da importacao. Serao preservados usuarios, permissoes e configuracoes.</span>
                </label>
                @error('confirmarLimpeza') <span class="ct-error">{{ $message }}</span> @enderror
            </section>

            <div class="ct-actions">
                <button type="submit" wire:loading.attr="disabled" class="ct-btn ct-btn-primary">
                    <span wire:loading.remove>Restaurar backup</span>
                    <span wire:loading>Importando...</span>
                </button>
            </div>
        </form>

        @if ($resultado)
            <section class="ct-restore-card">
                <h3 class="ct-restore-title">Resumo do ultimo restore</h3>
                <div class="ct-summary-grid">
                    @foreach ($resultado['tabelas'] ?? [] as $tabela => $quantidade)
                        <div class="ct-summary-item">
                            <strong>{{ $tabela }}</strong>
                            {{ number_format((int) $quantidade, 0, ',', '.') }} registros
                        </div>
                    @endforeach
                </div>
                <p class="ct-restore-subtitle">Duracao: {{ $resultado['duracao_segundos'] ?? 0 }} segundos.</p>

                @if (! empty($resultado['avisos']))
                    <div class="ct-warning" style="margin-top: 14px;">
                        <strong>Avisos</strong>
                        <ul class="ct-alert-list">
                            @foreach (array_slice($resultado['avisos'], 0, 20) as $aviso)
                                <li>{{ $aviso }}</li>
                            @endforeach
                        </ul>
                        @if (count($resultado['avisos']) > 20)
                            <p>Existem mais {{ count($resultado['avisos']) - 20 }} avisos.</p>
                        @endif
                    </div>
                @endif
            </section>
        @endif
    </div>
</x-filament-panels::page>
