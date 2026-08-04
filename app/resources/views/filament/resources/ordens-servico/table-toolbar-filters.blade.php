@php
    $statusOptions = collect(\App\Enums\OrdemServicoStatus::cases())->mapWithKeys(
        fn ($status) => [$status->value => $status->label()],
    );
    $tipoOptions = collect(\App\Enums\OrdemServicoTipo::cases())->mapWithKeys(
        fn ($tipo) => [$tipo->value => $tipo->label()],
    );
    $tecnicoOptions = \App\Models\Tecnico::query()
        ->where('is_ativo', true)
        ->orderBy('nome')
        ->pluck('nome', 'id');
@endphp

<style>
    .conectta-os-filterbar {
        display: grid;
        grid-template-columns: minmax(140px, 170px) minmax(140px, 170px) minmax(180px, 1fr) minmax(145px, 170px) minmax(145px, 170px) minmax(220px, 1.35fr) auto;
        gap: 12px;
        align-items: end;
        padding: 14px 16px;
        border-bottom: 1px solid #e5e7eb;
        background: #fff;
    }

    .conectta-os-filterbar label {
        display: grid;
        gap: 5px;
        min-width: 0;
        font-size: 13px;
        font-weight: 600;
        color: #374151;
    }

    .conectta-os-filterbar select,
    .conectta-os-filterbar input {
        width: 100%;
        min-height: 40px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #fff;
        color: #111827;
        font-size: 14px;
        line-height: 20px;
        padding: 8px 11px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
        outline: none;
    }

    .conectta-os-filterbar select:focus,
    .conectta-os-filterbar input:focus {
        border-color: #f59e0b;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, .16);
    }

    .conectta-os-filterbar button {
        min-height: 40px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #fff;
        color: #374151;
        font-size: 14px;
        font-weight: 700;
        padding: 8px 14px;
        white-space: nowrap;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    }

    .conectta-os-filterbar button:hover {
        background: #f9fafb;
    }

    @media (max-width: 1350px) {
        .conectta-os-filterbar {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 750px) {
        .conectta-os-filterbar {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="conectta-os-filterbar">
    <label>
        <span>Status</span>
        <select wire:model.live="ordemServicoStatusFiltro">
            <option value="">Todos</option>
            @foreach ($statusOptions as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </label>

    <label>
        <span>Tipo</span>
        <select wire:model.live="ordemServicoTipoFiltro">
            <option value="">Todos</option>
            @foreach ($tipoOptions as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </label>

    <label>
        <span>Técnico</span>
        <select wire:model.live="ordemServicoTecnicoFiltro">
            <option value="">Todos</option>
            @foreach ($tecnicoOptions as $id => $nome)
                <option value="{{ $id }}">{{ $nome }}</option>
            @endforeach
        </select>
    </label>

    <label>
        <span>Atendimento início</span>
        <input type="date" wire:model.live="ordemServicoPeriodoInicio" />
    </label>

    <label>
        <span>Atendimento final</span>
        <input type="date" wire:model.live="ordemServicoPeriodoFim" />
    </label>

    <label>
        <span>Pesquisar</span>
        <input
            type="search"
            wire:model.live.debounce.500ms="ordemServicoPesquisa"
            placeholder="Número da OS, cliente ou placa"
        />
    </label>

    <button type="button" wire:click="limparFiltrosOrdensServico">
        Limpar
    </button>
</div>
