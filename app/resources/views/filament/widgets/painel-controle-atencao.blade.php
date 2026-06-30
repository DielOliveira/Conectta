<x-filament-widgets::widget>
    <style>
        .ct-dashboard-section { display: grid; gap: 14px; }
        .ct-dashboard-card { background: #fff; border: 1px solid #d9dee7; border-radius: 8px; box-shadow: 0 1px 2px rgba(15, 23, 42, .04); padding: 20px; }
        .ct-dashboard-title { color: #0f172a; font-size: 20px; font-weight: 800; margin: 0; }
        .ct-attention-grid { display: grid; gap: 14px; grid-template-columns: repeat(4, minmax(0, 1fr)); margin-top: 14px; }
        .ct-attention-item { border: 1px solid #e2e8f0; border-left-width: 4px; border-radius: 8px; padding: 14px; }
        .ct-attention-item.danger { border-left-color: #ef4444; background: #fff7f7; }
        .ct-attention-item.warning { border-left-color: #f59e0b; background: #fffbeb; }
        .ct-attention-item.success { border-left-color: #22c55e; background: #f0fdf4; }
        .ct-attention-head { align-items: start; display: flex; gap: 10px; justify-content: space-between; }
        .ct-attention-name { color: #1f2937; font-size: 14px; font-weight: 800; line-height: 1.25; }
        .ct-attention-total { color: #0f172a; font-size: 26px; font-weight: 900; line-height: 1; }
        .ct-attention-lines { color: #475569; font-size: 12px; line-height: 1.4; margin: 12px 0 0; padding-left: 16px; }
        .ct-attention-lines li { margin-top: 4px; }
        .ct-attention-link { color: #b45309; display: inline-block; font-size: 12px; font-weight: 800; margin-top: 10px; text-decoration: none; }
        .ct-attention-link:hover { text-decoration: underline; }
        @media (max-width: 1200px) { .ct-attention-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 720px) { .ct-attention-grid { grid-template-columns: 1fr; } }
    </style>

    <section class="ct-dashboard-card ct-dashboard-section">
        <h2 class="ct-dashboard-title">Atencao necessaria</h2>

        <div class="ct-attention-grid">
            @forelse ($items as $item)
                <article class="ct-attention-item {{ $item['tipo'] }}">
                    <div class="ct-attention-head">
                        <div class="ct-attention-name">{{ $item['titulo'] }}</div>
                        <div class="ct-attention-total">{{ number_format((int) $item['total'], 0, ',', '.') }}</div>
                    </div>
                    <ul class="ct-attention-lines">
                        @foreach ($item['linhas'] as $linha)
                            <li>{{ $linha }}</li>
                        @endforeach
                    </ul>
                    <a class="ct-attention-link" href="{{ $item['url'] }}">Abrir</a>
                </article>
            @empty
                <article class="ct-attention-item success">
                    <div class="ct-attention-head">
                        <div class="ct-attention-name">Nada pendente para suas permissoes</div>
                        <div class="ct-attention-total">0</div>
                    </div>
                    <ul class="ct-attention-lines"><li>Quando houver algo relevante para revisar, aparece aqui.</li></ul>
                </article>
            @endforelse
        </div>
    </section>
</x-filament-widgets::widget>
