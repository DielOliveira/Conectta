<style>
    .ct-audit-detail {
        color: #0f172a;
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .ct-audit-header {
        background: #f8fafc;
        border: 1px solid #d9dee7;
        border-radius: 8px;
        overflow: hidden;
    }

    .ct-audit-section {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .ct-audit-header-main {
        align-items: flex-start;
        background: #fff;
        display: flex;
        gap: 16px;
        justify-content: space-between;
        padding: 16px;
    }

    .ct-audit-kicker {
        color: #64748b;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .04em;
        margin-bottom: 6px;
        text-transform: uppercase;
    }

    .ct-audit-title {
        color: #0f172a;
        font-size: 18px;
        font-weight: 850;
        line-height: 1.25;
        margin: 0;
    }

    .ct-audit-subtitle {
        color: #64748b;
        font-size: 13px;
        font-weight: 700;
        margin-top: 5px;
    }

    .ct-audit-badge {
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: 999px;
        color: #9a3412;
        display: inline-flex;
        flex-shrink: 0;
        font-size: 12px;
        font-weight: 800;
        line-height: 1;
        max-width: 100%;
        overflow-wrap: anywhere;
        padding: 8px 10px;
    }

    .ct-audit-meta-grid {
        border-top: 1px solid #e5e7eb;
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .ct-audit-meta {
        background: #f8fafc;
        border-right: 1px solid #e5e7eb;
        min-width: 0;
        padding: 12px 14px;
    }

    .ct-audit-meta:last-child {
        border-right: 0;
    }

    .ct-audit-meta-label {
        color: #64748b;
        display: block;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .04em;
        margin-bottom: 4px;
        text-transform: uppercase;
    }

    .ct-audit-meta-value {
        color: #0f172a;
        display: block;
        font-size: 13px;
        font-weight: 750;
        overflow-wrap: anywhere;
    }

    .ct-audit-description {
        background: #fff;
        border-top: 1px solid #e5e7eb;
        color: #334155;
        font-size: 13px;
        line-height: 1.45;
        padding: 12px 16px;
    }

    .ct-audit-section-title {
        align-items: center;
        color: #475569;
        display: flex;
        font-size: 11px;
        font-weight: 800;
        gap: 12px;
        justify-content: space-between;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .ct-audit-count {
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0;
        text-transform: none;
    }

    .ct-audit-empty,
    .ct-audit-box {
        background: #fff;
        border: 1px solid #d9dee7;
        border-radius: 8px;
        padding: 12px;
    }

    .ct-audit-empty {
        background: #f8fafc;
        color: #64748b;
    }

    .ct-audit-list {
        border: 1px solid #d9dee7;
        border-radius: 8px;
        overflow: hidden;
    }

    .ct-audit-change {
        background: #fff;
        border-bottom: 1px solid #e5e7eb;
        display: grid;
        gap: 0;
        grid-template-columns: minmax(160px, 220px) minmax(0, 1fr) minmax(0, 1fr) 92px;
    }

    .ct-audit-change:last-child {
        border-bottom: 0;
    }

    .ct-audit-change:nth-child(even) {
        background: #f8fafc;
    }

    .ct-audit-field,
    .ct-audit-value,
    .ct-audit-origin {
        padding: 12px;
    }

    .ct-audit-field {
        border-right: 1px solid #e5e7eb;
        font-weight: 800;
    }

    .ct-audit-value {
        border-right: 1px solid #e5e7eb;
        min-width: 0;
    }

    .ct-audit-origin {
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
    }

    .ct-audit-value-label {
        color: #64748b;
        display: block;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .04em;
        margin-bottom: 5px;
        text-transform: uppercase;
    }

    .ct-audit-value-text {
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        color: #1e293b;
        display: block;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
        font-size: 12px;
        line-height: 1.45;
        min-height: 32px;
        overflow-wrap: anywhere;
        padding: 8px;
        white-space: pre-wrap;
    }

    .ct-audit-value-after .ct-audit-value-text {
        background: #ecfdf5;
        border-color: #bbf7d0;
        color: #14532d;
    }

    .ct-audit-details {
        background: #fff;
        border: 1px solid #d9dee7;
        border-radius: 8px;
        overflow: hidden;
    }

    .ct-audit-details + .ct-audit-details {
        margin-top: -4px;
    }

    .ct-audit-summary {
        color: #475569;
        cursor: pointer;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .04em;
        padding: 11px 14px;
        text-transform: uppercase;
    }

    .ct-audit-details-body {
        border-top: 1px solid #e5e7eb;
        padding: 12px;
    }

    @media (max-width: 900px) {
        .ct-audit-header-main {
            display: block;
        }

        .ct-audit-badge {
            margin-top: 12px;
        }

        .ct-audit-meta-grid {
            grid-template-columns: 1fr;
        }

        .ct-audit-meta {
            border-right: 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .ct-audit-meta:last-child {
            border-bottom: 0;
        }

        .ct-audit-change {
            grid-template-columns: 1fr;
        }

        .ct-audit-field,
        .ct-audit-value,
        .ct-audit-origin {
            border-right: 0;
            padding: 10px 12px;
        }

        .ct-audit-value {
            border-top: 1px solid #e5e7eb;
        }
    }
</style>

<div class="ct-audit-detail text-sm">
    <div class="ct-audit-header">
        <div class="ct-audit-header-main">
            <div>
                <div class="ct-audit-kicker">Evento de auditoria</div>
                <h3 class="ct-audit-title">{{ $record->entidade_tipo }}</h3>
                <div class="ct-audit-subtitle">
                    @if ($record->entidade_id)
                        Registro #{{ $record->entidade_id }}
                    @else
                        Registro sem ID
                    @endif
                </div>
            </div>

            <span class="ct-audit-badge">{{ $record->acao }}</span>
        </div>

        <div class="ct-audit-meta-grid">
            <div class="ct-audit-meta">
                <span class="ct-audit-meta-label">Usuario</span>
                <span class="ct-audit-meta-value">{{ $record->user?->name ?? 'Sistema' }}</span>
            </div>

            <div class="ct-audit-meta">
                <span class="ct-audit-meta-label">Horario</span>
                <span class="ct-audit-meta-value">{{ $record->created_at?->format('d/m/Y H:i:s') }}</span>
            </div>

            <div class="ct-audit-meta">
                <span class="ct-audit-meta-label">Alteracoes</span>
                <span class="ct-audit-meta-value">{{ count($alteracoes) }} principais, {{ count($alteracoesTecnicas) }} tecnicas</span>
            </div>
        </div>

        <div class="ct-audit-description">{{ $record->descricao }}</div>
    </div>

    <div class="ct-audit-section">
        <div class="ct-audit-section-title">
            <span>Alteracoes identificadas</span>
            <span class="ct-audit-count">{{ count($alteracoes) }} campo(s)</span>
        </div>

        @if (count($alteracoes) > 0)
            <div class="ct-audit-list">
                @foreach ($alteracoes as $alteracao)
                    <div class="ct-audit-change">
                        <div class="ct-audit-field">{{ $alteracao['campo'] }}</div>
                        <div class="ct-audit-value">
                            <span class="ct-audit-value-label">Antes</span>
                            <span class="ct-audit-value-text">{{ $alteracao['antes'] }}</span>
                        </div>
                        <div class="ct-audit-value ct-audit-value-after">
                            <span class="ct-audit-value-label">Depois</span>
                            <span class="ct-audit-value-text">{{ $alteracao['depois'] }}</span>
                        </div>
                        <div class="ct-audit-origin">{{ $alteracao['origem'] }}</div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="ct-audit-empty">
                Nenhuma alteracao principal identificada. Verifique os dados tecnicos abaixo.
            </div>
        @endif
    </div>

    <details class="ct-audit-details">
        <summary class="ct-audit-summary">
            Campos tecnicos alterados ({{ count($alteracoesTecnicas) }})
        </summary>

        @if (count($alteracoesTecnicas) > 0)
            <div class="ct-audit-details-body">
                <div class="ct-audit-list">
                    @foreach ($alteracoesTecnicas as $alteracao)
                        <div class="ct-audit-change">
                            <div class="ct-audit-field">{{ $alteracao['campo'] }}</div>
                            <div class="ct-audit-value">
                                <span class="ct-audit-value-label">Antes</span>
                                <span class="ct-audit-value-text">{{ $alteracao['antes'] }}</span>
                            </div>
                            <div class="ct-audit-value ct-audit-value-after">
                                <span class="ct-audit-value-label">Depois</span>
                                <span class="ct-audit-value-text">{{ $alteracao['depois'] }}</span>
                            </div>
                            <div class="ct-audit-origin">{{ $alteracao['origem'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="ct-audit-details-body ct-audit-empty">
                Nenhum campo tecnico alterado.
            </div>
        @endif
    </details>

    <details class="ct-audit-details">
        <summary class="ct-audit-summary">
            Dados JSON originais
        </summary>

        <div class="ct-audit-details-body grid gap-4 lg:grid-cols-2">
            <div>
                <div class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Antes</div>
                <pre class="max-h-96 overflow-auto rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs leading-5 text-gray-950 dark:border-white/10 dark:bg-gray-950 dark:text-gray-100">{{ $antes !== '' ? $antes : 'Sem dados anteriores.' }}</pre>
            </div>

            <div>
                <div class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Depois</div>
                <pre class="max-h-96 overflow-auto rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs leading-5 text-gray-950 dark:border-white/10 dark:bg-gray-950 dark:text-gray-100">{{ $depois !== '' ? $depois : 'Sem dados posteriores.' }}</pre>
            </div>

            @if ($contexto !== '')
                <div class="lg:col-span-2">
                    <div class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Contexto</div>
                    <pre class="max-h-72 overflow-auto rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs leading-5 text-gray-950 dark:border-white/10 dark:bg-gray-950 dark:text-gray-100">{{ $contexto }}</pre>
                </div>
            @endif
        </div>
    </details>
</div>
