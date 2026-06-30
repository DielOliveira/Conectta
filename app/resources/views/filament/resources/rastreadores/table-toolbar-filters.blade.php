@php
    $statusOptions = \App\Models\StatusRastreador::query()
        ->whereIn('label', ['Ativo', 'Cancelado', 'Disponivel'])
        ->orderBy('order')
        ->orderBy('label')
        ->pluck('label', 'id');
@endphp

<style>
    .conectta-rastreadores-filterbar-wrap {
        overflow-x: auto;
        border-bottom: 1px solid #e5e7eb;
        background: #fff;
    }

    .conectta-rastreadores-filterbar {
        display: grid;
        grid-template-columns: 130px 150px 150px 150px 150px minmax(240px, 1fr) 88px;
        gap: 10px;
        align-items: end;
        min-width: 1120px;
        padding: 14px 16px;
        background: #fff;
    }

    .conectta-rastreadores-filterbar label {
        display: grid;
        gap: 5px;
        min-width: 0;
        font-size: 13px;
        font-weight: 600;
        color: #374151;
    }

    .conectta-rastreadores-filterbar select,
    .conectta-rastreadores-filterbar input {
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

    .conectta-rastreadores-filterbar select:focus,
    .conectta-rastreadores-filterbar input:focus {
        border-color: #f59e0b;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, .16);
    }

    .conectta-rastreadores-filterbar button {
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

    .conectta-rastreadores-filterbar button:hover {
        background: #f9fafb;
    }
</style>

<div class="conectta-rastreadores-filterbar-wrap">
    <div class="conectta-rastreadores-filterbar">
        <label>
            <span>Status</span>
            <select wire:model.live="rastreadorStatusFiltro">
                <option value="">Todos</option>
                @foreach ($statusOptions as $id => $label)
                    <option value="{{ $id }}">{{ $label }}</option>
                @endforeach
            </select>
        </label>

        <label>
            <span>Instala&ccedil;&atilde;o In&iacute;cio</span>
            <input type="date" wire:model.live="rastreadorInstalacaoInicio" />
        </label>

        <label>
            <span>Instala&ccedil;&atilde;o Final</span>
            <input type="date" wire:model.live="rastreadorInstalacaoFinal" />
        </label>

        <label>
            <span>Remo&ccedil;&atilde;o In&iacute;cio</span>
            <input type="date" wire:model.live="rastreadorRemocaoInicio" />
        </label>

        <label>
            <span>Remo&ccedil;&atilde;o Final</span>
            <input type="date" wire:model.live="rastreadorRemocaoFinal" />
        </label>

        <label>
            <span>Pesquisar</span>
            <input
                type="search"
                wire:model.live.debounce.500ms="rastreadorPesquisa"
                placeholder="IMEI, cliente, veiculo ou placa"
            />
        </label>

        <button type="button" wire:click="limparFiltrosRastreadores">
            Limpar
        </button>
    </div>
</div>