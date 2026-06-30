@php
    $statusOptions = \App\Models\StatusContrato::query()
        ->where('is_active', true)
        ->orderBy('order')
        ->orderBy('label')
        ->pluck('label', 'id');

    $tipoOptions = \App\Models\TipoContrato::query()
        ->where('is_active', true)
        ->orderBy('order')
        ->orderBy('label')
        ->pluck('label', 'id');
@endphp

<style>
    .conectta-contratos-filterbar-wrap {
        overflow-x: auto;
        border-bottom: 1px solid #e5e7eb;
        background: #fff;
    }

    .conectta-contratos-filterbar {
        display: grid;
        grid-template-columns: 160px 160px minmax(260px, 1fr) 88px;
        gap: 10px;
        align-items: end;
        min-width: 820px;
        padding: 14px 16px;
        background: #fff;
    }

    .conectta-contratos-filterbar label {
        display: grid;
        gap: 5px;
        min-width: 0;
        font-size: 13px;
        font-weight: 600;
        color: #374151;
    }

    .conectta-contratos-filterbar select,
    .conectta-contratos-filterbar input {
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

    .conectta-contratos-filterbar select:focus,
    .conectta-contratos-filterbar input:focus {
        border-color: #f59e0b;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, .16);
    }

    .conectta-contratos-filterbar button {
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

    .conectta-contratos-filterbar button:hover {
        background: #f9fafb;
    }
</style>

<div class="conectta-contratos-filterbar-wrap">
    <div class="conectta-contratos-filterbar">
        <label>
            <span>Status</span>
            <select wire:model.live="contratoStatusFiltro">
                <option value="">Todos</option>
                @foreach ($statusOptions as $id => $label)
                    <option value="{{ $id }}">{{ $label }}</option>
                @endforeach
            </select>
        </label>

        <label>
            <span>Tipo</span>
            <select wire:model.live="contratoTipoFiltro">
                <option value="">Todos</option>
                @foreach ($tipoOptions as $id => $label)
                    <option value="{{ $id }}">{{ $label }}</option>
                @endforeach
            </select>
        </label>

        <label>
            <span>Pesquisar</span>
            <input
                type="search"
                wire:model.live.debounce.500ms="contratoPesquisa"
                placeholder="Cliente, CPF/CNPJ, IMEI, veiculo ou placa"
            />
        </label>

        <button type="button" wire:click="limparFiltrosContratos">
            Limpar
        </button>
    </div>
</div>
