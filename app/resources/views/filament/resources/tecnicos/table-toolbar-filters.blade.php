<div class="conectta-tecnicos-filterbar-wrap">
    <style>
        .conectta-tecnicos-filterbar-wrap {
            border-bottom: 1px solid rgba(148, 163, 184, 0.25);
            padding: 18px 24px;
            overflow-x: auto;
            background: #ffffff;
        }

        .conectta-tecnicos-filterbar {
            align-items: end;
            display: grid;
            gap: 14px;
            grid-template-columns: 220px minmax(320px, 1fr) 96px;
            min-width: 720px;
        }

        .conectta-tecnicos-filterbar label {
            color: #111827;
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 7px;
        }

        .conectta-tecnicos-filterbar select,
        .conectta-tecnicos-filterbar input {
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            color: #111827;
            font-size: 14px;
            height: 42px;
            padding: 0 12px;
            width: 100%;
        }

        .conectta-tecnicos-filterbar button {
            align-items: center;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            color: #111827;
            display: inline-flex;
            font-size: 14px;
            font-weight: 600;
            height: 42px;
            justify-content: center;
            padding: 0 16px;
            width: 100%;
        }

        .conectta-tecnicos-filterbar button:hover {
            background: #f9fafb;
        }
    </style>

    <div class="conectta-tecnicos-filterbar">
        <div>
            <label for="tecnico-status-filtro">Status</label>
            <select id="tecnico-status-filtro" wire:model.live="tecnicoStatusFiltro">
                <option value="">Todos</option>
                <option value="1">Ativos</option>
                <option value="0">Inativos</option>
            </select>
        </div>

        <div>
            <label for="tecnico-pesquisa">Pesquisar</label>
            <input
                id="tecnico-pesquisa"
                type="search"
                placeholder="Nome, CPF ou telefone"
                wire:model.live.debounce.500ms="tecnicoPesquisa"
            />
        </div>

        <div>
            <button type="button" wire:click="limparFiltrosTecnicos">Limpar</button>
        </div>
    </div>
</div>