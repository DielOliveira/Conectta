<?php

namespace App\Support;

class BoletoStatus
{
    /** @var array<string, array{label: string, aliases: list<string>}> */
    private const GRUPOS = [
        'waiting_payment' => [
            'label' => 'Aguardando Pagamento',
            'aliases' => ['Aguardando Pagamento', 'waitingPayment', 'waiting_payment', 'waiting', 'pending'],
        ],
        'overdue' => [
            'label' => 'Atrasado',
            'aliases' => ['Atrasado', 'overdue', 'late'],
        ],
        'canceled' => [
            'label' => 'Cancelado',
            'aliases' => ['Cancelado', 'canceled', 'cancelled'],
        ],
        'paid' => [
            'label' => 'Pago',
            'aliases' => ['Pago', 'paid'],
        ],
        'processing' => [
            'label' => 'Processando',
            'aliases' => ['Processando', 'processing'],
        ],
    ];

    /** @param iterable<int, string> $status */
    public static function opcoes(iterable $status): array
    {
        return collect($status)
            ->map(fn (string $status): array => [
                'value' => self::chave($status),
                'label' => self::label($status),
            ])
            ->filter(fn (array $opcao): bool => $opcao['value'] !== '')
            ->unique('value')
            ->sortBy('label')
            ->values()
            ->all();
    }

    public static function label(?string $status): string
    {
        $status = trim((string) $status);

        if ($status === '') {
            return '';
        }

        $chave = self::chave($status);

        return self::GRUPOS[$chave]['label'] ?? str($status)->headline()->toString();
    }

    /** @return list<string> */
    public static function valoresDoFiltro(string $status): array
    {
        $chave = self::chave($status);

        return self::GRUPOS[$chave]['aliases'] ?? [$status];
    }

    private static function chave(?string $status): string
    {
        $status = trim((string) $status);

        if ($status === '') {
            return '';
        }

        $normalizado = str($status)
            ->lower()
            ->ascii()
            ->replace(['-', ' '], '_')
            ->toString();

        foreach (self::GRUPOS as $chave => $grupo) {
            if (collect($grupo['aliases'])->contains(
                fn (string $alias): bool => str($alias)->lower()->ascii()->replace(['-', ' '], '_')->toString() === $normalizado,
            )) {
                return $chave;
            }
        }

        return $status;
    }
}
