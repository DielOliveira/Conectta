<x-filament-widgets::widget>
    <style>
        .ct-shortcuts-card { background: #fff; border: 1px solid #d9dee7; border-radius: 8px; box-shadow: 0 1px 2px rgba(15, 23, 42, .04); padding: 20px; }
        .ct-shortcuts-title { color: #0f172a; font-size: 20px; font-weight: 800; margin: 0 0 14px; }
        .ct-shortcuts-list { display: flex; flex-wrap: wrap; gap: 10px; }
        .ct-shortcut { align-items: center; background: #fff; border: 1px solid #f59e0b; border-radius: 8px; color: #b45309; display: inline-flex; font-size: 14px; font-weight: 800; height: 40px; padding: 0 14px; text-decoration: none; }
        .ct-shortcut:hover { background: #fffbeb; }
        .ct-shortcuts-empty { color: #64748b; font-size: 14px; }
    </style>

    <section class="ct-shortcuts-card">
        <h2 class="ct-shortcuts-title">Atalhos rapidos</h2>
        <div class="ct-shortcuts-list">
            @forelse ($links as $link)
                <a class="ct-shortcut" href="{{ $link['url'] }}">{{ $link['label'] }}</a>
            @empty
                <span class="ct-shortcuts-empty">Nenhum atalho disponivel para suas permissoes.</span>
            @endforelse
        </div>
    </section>
</x-filament-widgets::widget>
