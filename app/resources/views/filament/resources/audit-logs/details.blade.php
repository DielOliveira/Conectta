<div class="space-y-5 text-sm">
    <div class="grid gap-3 md:grid-cols-2">
        <div>
            <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Usuario</div>
            <div class="mt-1 font-medium text-gray-950 dark:text-white">{{ $record->user?->name ?? 'Sistema' }}</div>
        </div>

        <div>
            <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Horario</div>
            <div class="mt-1 font-medium text-gray-950 dark:text-white">{{ $record->created_at?->format('d/m/Y H:i:s') }}</div>
        </div>

        <div>
            <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Acao</div>
            <div class="mt-1 font-medium text-gray-950 dark:text-white">{{ $record->acao }}</div>
        </div>

        <div>
            <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Entidade</div>
            <div class="mt-1 font-medium text-gray-950 dark:text-white">
                {{ $record->entidade_tipo }}
                @if ($record->entidade_id)
                    #{{ $record->entidade_id }}
                @endif
            </div>
        </div>
    </div>

    <div>
        <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Descricao</div>
        <div class="mt-1 rounded-lg border border-gray-200 bg-white p-3 text-gray-950 dark:border-white/10 dark:bg-white/5 dark:text-white">
            {{ $record->descricao }}
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div>
            <div class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Antes</div>
            <pre class="max-h-96 overflow-auto rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs leading-5 text-gray-950 dark:border-white/10 dark:bg-gray-950 dark:text-gray-100">{{ $antes !== '' ? $antes : 'Sem dados anteriores.' }}</pre>
        </div>

        <div>
            <div class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Depois</div>
            <pre class="max-h-96 overflow-auto rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs leading-5 text-gray-950 dark:border-white/10 dark:bg-gray-950 dark:text-gray-100">{{ $depois !== '' ? $depois : 'Sem dados posteriores.' }}</pre>
        </div>
    </div>

    @if ($contexto !== '')
        <div>
            <div class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Contexto</div>
            <pre class="max-h-72 overflow-auto rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs leading-5 text-gray-950 dark:border-white/10 dark:bg-gray-950 dark:text-gray-100">{{ $contexto }}</pre>
        </div>
    @endif
</div>
