<x-filament-panels::page>
    <style>
        .ct-billing-page {
            --ct-primary: #f59e0b;
            --ct-primary-strong: #d97706;
            display: grid;
            gap: 18px;
            width: 100%;
        }

        .ct-billing-toolbar {
            align-items: end;
            display: grid;
            gap: 14px;
            grid-template-columns: minmax(160px, 220px) minmax(220px, 320px) minmax(160px, 220px) 1fr;
        }

        .ct-billing-field { display: grid; gap: 6px; }
        .ct-billing-label { color: #334155; font-size: 14px; font-weight: 600; }

        .ct-billing-select {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            color: #0f172a;
            font-size: 15px;
            height: 42px;
            padding: 0 12px;
            width: 100%;
        }

        .ct-billing-select:focus {
            border-color: var(--ct-primary);
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.18);
            outline: none;
        }

        .ct-billing-btn {
            align-items: center;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            color: #111827;
            cursor: pointer;
            display: inline-flex;
            font-size: 14px;
            font-weight: 800;
            height: 42px;
            justify-content: center;
            padding: 0 18px;
        }

        .ct-billing-content {
            align-items: start;
            display: grid;
            gap: 18px;
            grid-template-columns: minmax(560px, 1fr) minmax(360px, 0.78fr);
        }

        .ct-billing-table-wrap,
        .ct-billing-chart-card {
            background: #ffffff;
            border: 1px solid #d9dee7;
            border-radius: 8px;
            overflow: hidden;
        }

        .ct-billing-table { border-collapse: collapse; font-size: 14px; width: 100%; }

        .ct-billing-table th {
            background: #ffffff;
            color: #4b5563;
            font-weight: 800;
            padding: 13px 18px;
            text-align: left;
        }

        .ct-billing-table td {
            border-top: 1px solid #e2e8f0;
            color: #1f2937;
            padding: 14px 18px;
        }

        .ct-billing-table tr.is-open td { background: #bae6fd; }
        .ct-billing-month { font-weight: 800; }
        .ct-billing-number { text-align: left; white-space: nowrap; }

        .ct-billing-action {
            align-items: center;
            background: transparent;
            border: 0;
            color: #111827;
            cursor: pointer;
            display: inline-flex;
            font-size: 17px;
            font-weight: 800;
            height: 28px;
            justify-content: center;
            padding: 0;
            width: 28px;
        }

        .ct-billing-open-dot {
            background: #0f172a;
            border-radius: 999px;
            display: inline-block;
            height: 6px;
            margin-left: 7px;
            vertical-align: middle;
            width: 6px;
        }

        .ct-billing-total td { background: #f8fafc; font-weight: 900; }

        .ct-billing-chart-stack {
            display: grid;
            gap: 18px;
        }

        .ct-billing-chart-card {
            display: grid;
            gap: 14px;
            padding: 18px;
        }

        .ct-billing-chart-head {
            display: flex;
            gap: 12px;
            justify-content: space-between;
        }

        .ct-billing-chart-title { color: #0f172a; font-size: 16px; font-weight: 900; }
        .ct-billing-chart-subtitle { color: #64748b; font-size: 12px; margin-top: 2px; }

        .ct-billing-legend {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: flex-end;
        }

        .ct-billing-legend-item {
            align-items: center;
            color: #475569;
            display: inline-flex;
            font-size: 12px;
            font-weight: 700;
            gap: 6px;
        }

        .ct-billing-legend-dot { border-radius: 999px; height: 9px; width: 9px; }
        .ct-billing-chart-svg { display: block; height: auto; overflow: visible; width: 100%; }
        .ct-billing-chart-grid { stroke: #e2e8f0; stroke-width: 1; }
        .ct-billing-chart-axis-label { fill: #64748b; font-size: 11px; font-weight: 700; }

        .ct-billing-chart-line-planned,
        .ct-billing-chart-line-launched,
        .ct-billing-chart-line-received {
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 3;
        }

        .ct-billing-chart-line-planned { stroke: #2563eb; }
        .ct-billing-chart-line-launched { stroke: #f59e0b; }
        .ct-billing-chart-line-received { stroke: #16a34a; }
        .ct-billing-chart-point-planned { fill: #2563eb; }
        .ct-billing-chart-point-launched { fill: #f59e0b; }
        .ct-billing-chart-point-received { fill: #16a34a; }

        .ct-billing-chart-totals {
            border-top: 1px solid #e2e8f0;
            display: grid;
            gap: 8px;
            grid-template-columns: 1fr 1fr 1fr;
            padding-top: 12px;
        }

        .ct-billing-total-label { color: #64748b; font-size: 12px; font-weight: 700; }
        .ct-billing-total-value { color: #0f172a; font-size: 15px; font-weight: 900; margin-top: 2px; }

        @media (max-width: 1180px) { .ct-billing-content { grid-template-columns: 1fr; } }

        @media (max-width: 760px) {
            .ct-billing-toolbar { grid-template-columns: 1fr; }
            .ct-billing-table-wrap { overflow-x: auto; }
            .ct-billing-table { min-width: 700px; }
            .ct-billing-chart-head { display: grid; }
            .ct-billing-legend { justify-content: flex-start; }
        }
    </style>

    @php
        $linhas = $this->linhasFaturamento();
        $mesAtual = (int) now()->month;
        $anoAtual = (int) now()->year;
        $linhasGrafico = ((int) $ano === $anoAtual)
            ? $linhas->where('mes', '<=', $mesAtual)->values()
            : $linhas->values();
        $panoramaAnual = $this->panoramaAnual();

        $chartWidth = 620;
        $chartHeight = 300;
        $chartLeft = 42;
        $chartRight = 18;
        $chartTop = 18;
        $chartBottom = 42;
        $plotWidth = $chartWidth - $chartLeft - $chartRight;
        $plotHeight = $chartHeight - $chartTop - $chartBottom;
        $monthlySteps = max(1, $linhasGrafico->count() - 1);
        $maxValor = max(1, (float) $linhasGrafico->max('total_planejado'), (float) $linhasGrafico->max('total_lancado'), (float) $linhasGrafico->max('total_recebido'));
        $pontosPlanejado = [];
        $pontosLancado = [];
        $pontosRecebido = [];

        foreach ($linhasGrafico as $index => $linha) {
            $x = $chartLeft + (($plotWidth / $monthlySteps) * $index);
            $yPlanejado = $chartTop + $plotHeight - (((float) $linha['total_planejado'] / $maxValor) * $plotHeight);
            $yLancado = $chartTop + $plotHeight - (((float) $linha['total_lancado'] / $maxValor) * $plotHeight);
            $yRecebido = $chartTop + $plotHeight - (((float) $linha['total_recebido'] / $maxValor) * $plotHeight);
            $pontosPlanejado[] = round($x, 2) . ',' . round($yPlanejado, 2);
            $pontosLancado[] = round($x, 2) . ',' . round($yLancado, 2);
            $pontosRecebido[] = round($x, 2) . ',' . round($yRecebido, 2);
        }

        $annualWidth = 620;
        $annualHeight = 300;
        $annualLeft = 42;
        $annualRight = 18;
        $annualTop = 18;
        $annualBottom = 42;
        $annualPlotWidth = $annualWidth - $annualLeft - $annualRight;
        $annualPlotHeight = $annualHeight - $annualTop - $annualBottom;
        $annualSteps = max(1, $panoramaAnual->count() - 1);
        $maxAnual = max(1, (float) $panoramaAnual->max('total_planejado'), (float) $panoramaAnual->max('total_lancado'), (float) $panoramaAnual->max('total_recebido'));
        $pontosAnualPlanejado = [];
        $pontosAnualLancado = [];
        $pontosAnualRecebido = [];

        foreach ($panoramaAnual as $index => $linhaAnual) {
            $x = $annualLeft + (($annualPlotWidth / $annualSteps) * $index);
            if ($panoramaAnual->count() === 1) {
                $x = $annualLeft + ($annualPlotWidth / 2);
            }
            $yPlanejado = $annualTop + $annualPlotHeight - (((float) $linhaAnual['total_planejado'] / $maxAnual) * $annualPlotHeight);
            $yLancado = $annualTop + $annualPlotHeight - (((float) $linhaAnual['total_lancado'] / $maxAnual) * $annualPlotHeight);
            $yRecebido = $annualTop + $annualPlotHeight - (((float) $linhaAnual['total_recebido'] / $maxAnual) * $annualPlotHeight);
            $pontosAnualPlanejado[] = round($x, 2) . ',' . round($yPlanejado, 2);
            $pontosAnualLancado[] = round($x, 2) . ',' . round($yLancado, 2);
            $pontosAnualRecebido[] = round($x, 2) . ',' . round($yRecebido, 2);
        }

        $comparativoMensal = $this->comparativoMensal();
        $comparisonWidth = 620;
        $comparisonHeight = 300;
        $comparisonLeft = 42;
        $comparisonRight = 18;
        $comparisonTop = 18;
        $comparisonBottom = 42;
        $comparisonPlotWidth = $comparisonWidth - $comparisonLeft - $comparisonRight;
        $comparisonPlotHeight = $comparisonHeight - $comparisonTop - $comparisonBottom;
        $comparisonSteps = max(1, $comparativoMensal->count() - 1);
        $maxComparativo = max(1, (float) $comparativoMensal->max('total_recebido'));
        $pontosComparativo = [];

        foreach ($comparativoMensal as $index => $linhaComparativa) {
            $x = $comparisonLeft + (($comparisonPlotWidth / $comparisonSteps) * $index);
            if ($comparativoMensal->count() === 1) {
                $x = $comparisonLeft + ($comparisonPlotWidth / 2);
            }
            $yRecebido = $comparisonTop + $comparisonPlotHeight - (((float) $linhaComparativa['total_recebido'] / $maxComparativo) * $comparisonPlotHeight);
            $pontosComparativo[] = round($x, 2) . ',' . round($yRecebido, 2);
        }
    @endphp

    <div class="ct-billing-page">
        <div class="ct-billing-toolbar">
            <label class="ct-billing-field">
                <span class="ct-billing-label">Ano</span>
                <select wire:model.live="ano" class="ct-billing-select">
                    @foreach ($this->anosDisponiveis() as $anoDisponivel)
                        <option value="{{ $anoDisponivel }}">{{ $anoDisponivel }}</option>
                    @endforeach
                </select>
            </label>

            <label class="ct-billing-field">
                <span class="ct-billing-label">Grafico</span>
                <select wire:model.live="graficoSelecionado" class="ct-billing-select">
                    @foreach ($this->graficosDisponiveis() as $valorGrafico => $labelGrafico)
                        <option value="{{ $valorGrafico }}">{{ $labelGrafico }}</option>
                    @endforeach
                </select>
            </label>

            @if ($graficoSelecionado === 'comparativo_mes')
                <label class="ct-billing-field">
                    <span class="ct-billing-label">Mes</span>
                    <select wire:model.live="mesComparativo" class="ct-billing-select">
                        @foreach (range(1, 12) as $mesOpcao)
                            <option value="{{ $mesOpcao }}">{{ $this->mesNome($mesOpcao) }}</option>
                        @endforeach
                    </select>
                </label>
            @else
                <div></div>
            @endif

            <div></div>
        </div>

        <div class="ct-billing-content">
            <div class="ct-billing-table-wrap">
                <table class="ct-billing-table">
                    <thead>
                        <tr>
                            <th style="width: 58px;"></th>
                            <th>Mes</th>
                            <th>Total Planejado</th>
                            <th>Total Lancado</th>
                            <th>Total Recebido</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($linhas as $linha)
                            <tr class="{{ $linha['is_aberto'] ? 'is-open' : '' }}" wire:key="faturamento-{{ $ano }}-{{ $linha['mes'] }}">
                                <td>
                                    <button type="button" wire:click="alternarAberto({{ $linha['mes'] }})" wire:confirm="Deseja alterar o mes aberto?" class="ct-billing-action" title="Alterar mes aberto">&#8644;</button>
                                </td>
                                <td class="ct-billing-month">
                                    {{ $linha['nome'] }}
                                    @if ($linha['is_aberto'])
                                        <span class="ct-billing-open-dot" title="Mes aberto"></span>
                                    @endif
                                </td>
                                <td class="ct-billing-number">{{ $this->moeda($linha['total_planejado']) }}</td>
                                <td class="ct-billing-number">{{ $this->moeda($linha['total_lancado']) }}</td>
                                <td class="ct-billing-number">{{ $this->moeda($linha['total_recebido']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="ct-billing-total">
                            <td></td>
                            <td>Total</td>
                            <td class="ct-billing-number">{{ $this->moeda($this->totalPlanejadoAno()) }}</td>
                            <td class="ct-billing-number">{{ $this->moeda($this->totalLancadoAno()) }}</td>
                            <td class="ct-billing-number">{{ $this->moeda($this->totalRecebidoAno()) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="ct-billing-chart-stack">
                @if ($graficoSelecionado === 'mensal')
                <section class="ct-billing-chart-card" aria-label="Grafico de faturamento mensal">
                <div class="ct-billing-chart-head">
                    <div>
                        <div class="ct-billing-chart-title">Faturamento {{ $ano }}</div>
                        <div class="ct-billing-chart-subtitle">
                            {{ (int) $ano === $anoAtual ? 'Ate ' . $this->mesNome($mesAtual) : 'Totais mensais do ano' }}
                        </div>
                    </div>
                    <div class="ct-billing-legend">
                        <span class="ct-billing-legend-item"><span class="ct-billing-legend-dot" style="background: #2563eb;"></span>Planejado</span>
                        <span class="ct-billing-legend-item"><span class="ct-billing-legend-dot" style="background: #f59e0b;"></span>Lancado</span>
                        <span class="ct-billing-legend-item"><span class="ct-billing-legend-dot" style="background: #16a34a;"></span>Recebido</span>
                    </div>
                </div>

                <svg class="ct-billing-chart-svg" viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" role="img" aria-label="Grafico de linha dos totais mensais">
                    @for ($i = 0; $i <= 4; $i++)
                        @php
                            $y = $chartTop + (($plotHeight / 4) * $i);
                            $valorGrade = $maxValor - (($maxValor / 4) * $i);
                        @endphp
                        <line class="ct-billing-chart-grid" x1="{{ $chartLeft }}" y1="{{ $y }}" x2="{{ $chartWidth - $chartRight }}" y2="{{ $y }}" />
                        <text class="ct-billing-chart-axis-label" x="0" y="{{ $y + 4 }}">{{ number_format($valorGrade / 1000, 0, ',', '.') }}k</text>
                    @endfor

                    <polyline class="ct-billing-chart-line-planned" points="{{ implode(' ', $pontosPlanejado) }}" />
                    <polyline class="ct-billing-chart-line-launched" points="{{ implode(' ', $pontosLancado) }}" />
                    <polyline class="ct-billing-chart-line-received" points="{{ implode(' ', $pontosRecebido) }}" />

                    @foreach ($linhasGrafico as $index => $linha)
                        @php
                            $x = $chartLeft + (($plotWidth / $monthlySteps) * $index);
                            $yPlanejado = $chartTop + $plotHeight - (((float) $linha['total_planejado'] / $maxValor) * $plotHeight);
                            $yLancado = $chartTop + $plotHeight - (((float) $linha['total_lancado'] / $maxValor) * $plotHeight);
                            $yRecebido = $chartTop + $plotHeight - (((float) $linha['total_recebido'] / $maxValor) * $plotHeight);
                        @endphp
                        <circle class="ct-billing-chart-point-planned" cx="{{ $x }}" cy="{{ $yPlanejado }}" r="4" />
                        <circle class="ct-billing-chart-point-launched" cx="{{ $x }}" cy="{{ $yLancado }}" r="4" />
                        <circle class="ct-billing-chart-point-received" cx="{{ $x }}" cy="{{ $yRecebido }}" r="4" />
                        <text class="ct-billing-chart-axis-label" x="{{ $x }}" y="{{ $chartHeight - 12 }}" text-anchor="middle">{{ substr($linha['nome'], 0, 3) }}</text>
                    @endforeach
                </svg>

                <div class="ct-billing-chart-totals">
                    <div>
                        <div class="ct-billing-total-label">Planejado no ano</div>
                        <div class="ct-billing-total-value">{{ $this->moeda($this->totalPlanejadoAno()) }}</div>
                    </div>
                    <div>
                        <div class="ct-billing-total-label">Lancado no ano</div>
                        <div class="ct-billing-total-value">{{ $this->moeda($this->totalLancadoAno()) }}</div>
                    </div>
                    <div>
                        <div class="ct-billing-total-label">Recebido no ano</div>
                        <div class="ct-billing-total-value">{{ $this->moeda($this->totalRecebidoAno()) }}</div>
                    </div>
                </div>
                </section>

                @elseif ($graficoSelecionado === 'panorama')
                <section class="ct-billing-chart-card" aria-label="Panorama anual de faturamento">
            <div class="ct-billing-chart-head">
                <div>
                    <div class="ct-billing-chart-title">Panorama anual</div>
                    <div class="ct-billing-chart-subtitle">Todos os anos disponiveis na base</div>
                </div>
                <div class="ct-billing-legend">
                    <span class="ct-billing-legend-item"><span class="ct-billing-legend-dot" style="background: #2563eb;"></span>Planejado</span>
                    <span class="ct-billing-legend-item"><span class="ct-billing-legend-dot" style="background: #f59e0b;"></span>Lancado</span>
                    <span class="ct-billing-legend-item"><span class="ct-billing-legend-dot" style="background: #16a34a;"></span>Recebido</span>
                </div>
            </div>

            <svg class="ct-billing-chart-svg" viewBox="0 0 {{ $annualWidth }} {{ $annualHeight }}" role="img" aria-label="Grafico de linha do panorama anual">
                @for ($i = 0; $i <= 4; $i++)
                    @php
                        $y = $annualTop + (($annualPlotHeight / 4) * $i);
                        $valorGrade = $maxAnual - (($maxAnual / 4) * $i);
                    @endphp
                    <line class="ct-billing-chart-grid" x1="{{ $annualLeft }}" y1="{{ $y }}" x2="{{ $annualWidth - $annualRight }}" y2="{{ $y }}" />
                    <text class="ct-billing-chart-axis-label" x="0" y="{{ $y + 4 }}">{{ number_format($valorGrade / 1000, 0, ',', '.') }}k</text>
                @endfor

                <polyline class="ct-billing-chart-line-planned" points="{{ implode(' ', $pontosAnualPlanejado) }}" />
                <polyline class="ct-billing-chart-line-launched" points="{{ implode(' ', $pontosAnualLancado) }}" />
                <polyline class="ct-billing-chart-line-received" points="{{ implode(' ', $pontosAnualRecebido) }}" />

                @foreach ($panoramaAnual as $index => $linhaAnual)
                    @php
                        $x = $annualLeft + (($annualPlotWidth / $annualSteps) * $index);
                        if ($panoramaAnual->count() === 1) {
                            $x = $annualLeft + ($annualPlotWidth / 2);
                        }
                        $yPlanejado = $annualTop + $annualPlotHeight - (((float) $linhaAnual['total_planejado'] / $maxAnual) * $annualPlotHeight);
                        $yLancado = $annualTop + $annualPlotHeight - (((float) $linhaAnual['total_lancado'] / $maxAnual) * $annualPlotHeight);
                        $yRecebido = $annualTop + $annualPlotHeight - (((float) $linhaAnual['total_recebido'] / $maxAnual) * $annualPlotHeight);
                    @endphp
                    <circle class="ct-billing-chart-point-planned" cx="{{ $x }}" cy="{{ $yPlanejado }}" r="4" />
                    <circle class="ct-billing-chart-point-launched" cx="{{ $x }}" cy="{{ $yLancado }}" r="4" />
                    <circle class="ct-billing-chart-point-received" cx="{{ $x }}" cy="{{ $yRecebido }}" r="4" />
                    <text class="ct-billing-chart-axis-label" x="{{ $x }}" y="{{ $annualHeight - 12 }}" text-anchor="middle">{{ $linhaAnual['ano'] }}</text>
                @endforeach
            </svg>
                </section>
                @else
                <section class="ct-billing-chart-card" aria-label="Comparativo mensal de faturamento">
                    <div class="ct-billing-chart-head">
                        <div>
                            <div class="ct-billing-chart-title">Comparativo de {{ $this->mesNome($mesComparativo) }}</div>
                            <div class="ct-billing-chart-subtitle">Total recebido no mes em cada ano</div>
                        </div>
                        <div class="ct-billing-legend">
                            <span class="ct-billing-legend-item"><span class="ct-billing-legend-dot" style="background: #16a34a;"></span>Recebido</span>
                        </div>
                    </div>

                    <svg class="ct-billing-chart-svg" viewBox="0 0 {{ $comparisonWidth }} {{ $comparisonHeight }}" role="img" aria-label="Grafico comparativo do recebido no mes por ano">
                        @for ($i = 0; $i <= 4; $i++)
                            @php
                                $y = $comparisonTop + (($comparisonPlotHeight / 4) * $i);
                                $valorGrade = $maxComparativo - (($maxComparativo / 4) * $i);
                            @endphp
                            <line class="ct-billing-chart-grid" x1="{{ $comparisonLeft }}" y1="{{ $y }}" x2="{{ $comparisonWidth - $comparisonRight }}" y2="{{ $y }}" />
                            <text class="ct-billing-chart-axis-label" x="0" y="{{ $y + 4 }}">{{ number_format($valorGrade / 1000, 0, ',', '.') }}k</text>
                        @endfor

                        <polyline class="ct-billing-chart-line-received" points="{{ implode(' ', $pontosComparativo) }}" />

                        @foreach ($comparativoMensal as $index => $linhaComparativa)
                            @php
                                $x = $comparisonLeft + (($comparisonPlotWidth / $comparisonSteps) * $index);
                                if ($comparativoMensal->count() === 1) {
                                    $x = $comparisonLeft + ($comparisonPlotWidth / 2);
                                }
                                $yRecebido = $comparisonTop + $comparisonPlotHeight - (((float) $linhaComparativa['total_recebido'] / $maxComparativo) * $comparisonPlotHeight);
                            @endphp
                            <circle class="ct-billing-chart-point-received" cx="{{ $x }}" cy="{{ $yRecebido }}" r="4" />
                            <text class="ct-billing-chart-axis-label" x="{{ $x }}" y="{{ $comparisonHeight - 12 }}" text-anchor="middle">{{ $linhaComparativa['ano'] }}</text>
                        @endforeach
                    </svg>

                    <div class="ct-billing-chart-totals">
                        @foreach ($comparativoMensal as $linhaComparativa)
                            <div>
                                <div class="ct-billing-total-label">{{ $linhaComparativa['ano'] }}</div>
                                <div class="ct-billing-total-value">{{ $this->moeda($linhaComparativa['total_recebido']) }}</div>
                            </div>
                        @endforeach
                    </div>
                </section>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
