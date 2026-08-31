@php
    [$background, $border, $text, $dot] = match ($status) {
        \App\Enums\OrdemServicoStatus::ABERTA => ['#f9fafb', '#e5e7eb', '#374151', '#6b7280'],
        \App\Enums\OrdemServicoStatus::ENVIADA => ['#fffbeb', '#fde68a', '#92400e', '#d97706'],
        \App\Enums\OrdemServicoStatus::ACEITA => ['#ecfeff', '#a5f3fc', '#155e75', '#0891b2'],
        \App\Enums\OrdemServicoStatus::EM_ATENDIMENTO => ['#f5f3ff', '#ddd6fe', '#5b21b6', '#7c3aed'],
        \App\Enums\OrdemServicoStatus::AGUARDANDO_CORRECAO_CADASTRAL => ['#fff7ed', '#fed7aa', '#9a3412', '#ea580c'],
        \App\Enums\OrdemServicoStatus::EM_CONFERENCIA => ['#eef2ff', '#c7d2fe', '#3730a3', '#4f46e5'],
        \App\Enums\OrdemServicoStatus::PENDENTE => ['#fef2f2', '#fecaca', '#991b1b', '#dc2626'],
        \App\Enums\OrdemServicoStatus::FINALIZADA => ['#f0fdf4', '#bbf7d0', '#166534', '#16a34a'],
        \App\Enums\OrdemServicoStatus::CANCELADA => ['#f3f4f6', '#d1d5db', '#4b5563', '#4b5563'],
    };
@endphp

<span class="ct-os-status-badge" style="display: inline-flex; align-items: center; gap: 7px; width: max-content; max-width: 100%; border: 1px solid {{ $border }}; border-radius: 999px; background: {{ $background }}; padding: 5px 10px; color: {{ $text }}; font-size: 12px; font-weight: 800; line-height: 1.2;">
    <span aria-hidden="true" style="width: 7px; height: 7px; flex: none; border-radius: 999px; background: {{ $dot }};"></span>
    {{ $status->label() }}
</span>
