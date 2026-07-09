<x-filament-widgets::widget>
    <style>
        .ct-notes-card {
            background: #ffffff;
            border: 1px solid #d9dee7;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
            display: grid;
            gap: 12px;
            padding: 18px;
        }

        .ct-notes-head {
            align-items: center;
            display: flex;
            gap: 12px;
            justify-content: space-between;
        }

        .ct-notes-title {
            color: #0f172a;
            font-size: 18px;
            font-weight: 800;
            margin: 0;
        }

        .ct-notes-status {
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            min-height: 18px;
            text-align: right;
        }

        .ct-notes-textarea {
            background: #fffdf8;
            border: 1px solid #f1c978;
            border-radius: 8px;
            color: #0f172a;
            font-size: 14px;
            line-height: 1.5;
            min-height: 118px;
            outline: none;
            padding: 12px 14px;
            resize: vertical;
            width: 100%;
        }

        .ct-notes-textarea:focus {
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, .18);
        }

        @media (max-width: 700px) {
            .ct-notes-head {
                align-items: start;
                display: grid;
            }

            .ct-notes-status {
                text-align: left;
            }
        }
    </style>

    <section class="ct-notes-card">
        <div class="ct-notes-head">
            <h2 class="ct-notes-title">Anotações e lembretes</h2>
            <div class="ct-notes-status">
                <span wire:loading.delay wire:target="conteudo">Salvando...</span>
                <span wire:loading.remove wire:target="conteudo">
                    @if ($salvoEm)
                        Salvo às {{ $salvoEm }}
                    @endif
                </span>
            </div>
        </div>

        <textarea
            wire:model.live.debounce.900ms="conteudo"
            class="ct-notes-textarea"
            placeholder="Escreva suas anotações aqui e elas ficarão salvas."
        ></textarea>
    </section>
</x-filament-widgets::widget>
