@php
    $statusOptions = \App\Models\StatusCliente::query()
        ->orderBy('order')
        ->orderBy('label')
        ->pluck('label', 'id');
@endphp

<style>
.conectta-clientes-filterbar {
        display: grid;
        grid-template-columns: minmax(150px, 190px) minmax(150px, 180px) minmax(150px, 180px) minmax(220px, 1fr) auto;
        gap: 12px;
        align-items: end;
        padding: 14px 16px;
        border-bottom: 1px solid #e5e7eb;
        background: #fff;
    }

    .conectta-clientes-filterbar label {
        display: grid;
        gap: 5px;
        min-width: 0;
        font-size: 13px;
        font-weight: 600;
        color: #374151;
    }

    .conectta-clientes-filterbar select,
    .conectta-clientes-filterbar input {
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

    .conectta-clientes-filterbar select:focus,
    .conectta-clientes-filterbar input:focus {
        border-color: #f59e0b;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, .16);
    }

    .conectta-clientes-filterbar button {
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

    .conectta-clientes-filterbar button:hover {
        background: #f9fafb;
    }

    @media (max-width: 1100px) {
        .conectta-clientes-filterbar {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 700px) {
        .conectta-clientes-filterbar {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="conectta-clientes-filterbar">
    <label>
        <span>Status</span>
        <select wire:model.live="clienteStatusFiltro">
            <option value="">Todos</option>
            @foreach ($statusOptions as $id => $label)
                <option value="{{ $id }}">{{ $label }}</option>
            @endforeach
        </select>
    </label>

    <label>
        <span>Instala&ccedil;&atilde;o In&iacute;cio</span>
        <input type="date" wire:model.live="clienteCadastroInicio" />
    </label>

    <label>
        <span>Instala&ccedil;&atilde;o Final</span>
        <input type="date" wire:model.live="clienteCadastroFinal" />
    </label>

    <label>
        <span>Pesquisar</span>
        <input
            type="search"
            wire:model.live.debounce.500ms="clientePesquisa"
            placeholder="Nome, CPF ou CNPJ"
        />
    </label>

    <button type="button" wire:click="limparFiltrosClientes">
        Limpar
    </button>
</div>