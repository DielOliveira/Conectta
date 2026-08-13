<?php

namespace App\Services\Tracksolid;

use App\Models\TracksolidImportacao;
use Illuminate\Support\Facades\DB;

class TracksolidDumpImportService
{
    public function import(string $arquivo, string $sha256, array $rows): TracksolidImportacao
    {
        return DB::transaction(function () use ($arquivo, $sha256, $rows): TracksolidImportacao {
            $importacao = TracksolidImportacao::query()->create([
                'arquivo' => basename($arquivo),
                'sha256' => $sha256,
                'total_registros' => count($rows),
                'total_tags' => 0,
                'total_rastreadores' => 0,
            ]);

            $tags = 0;
            $now = now();

            foreach (array_chunk($rows, 500, true) as $chunk) {
                $insert = [];

                foreach ($chunk as $index => $row) {
                    $modelo = $this->text($row['Model'] ?? null);
                    $isTag = mb_strtoupper($modelo) === 'TAG';
                    $placaInformada = $this->plate($row['License Plate No.'] ?? null);
                    $placaExtraida = $this->extractPlate($row['Device Name'] ?? null);
                    $tags += (int) $isTag;

                    $insert[] = [
                        'importacao_id' => $importacao->id,
                        'linha' => $index + 2,
                        'conta' => $this->text($row['Account'] ?? null),
                        'cliente_nome' => $this->text($row['Customer Name'] ?? null),
                        'dispositivo_nome' => $this->text($row['Device Name'] ?? null),
                        'imei' => $this->digits($row['IMEI'] ?? null),
                        'modelo' => $modelo,
                        'is_tag' => $isTag,
                        'sim' => $this->digits($row['SIM'] ?? null),
                        'iccid' => $this->digits($row['ICCID'] ?? null),
                        'placa_informada' => $placaInformada,
                        'placa_extraida' => $placaExtraida,
                        'placa' => $placaInformada ?: $placaExtraida,
                        'grupo' => $this->text($row['Group'] ?? null),
                        'data_ativacao' => $this->text($row['Activated Date'] ?? null),
                        'expiracao_assinatura' => $this->text($row['Subscription Expiration'] ?? null),
                        'data_instalacao' => $this->text($row['Installation Time'] ?? null),
                        'dados_brutos' => json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                DB::table('tracksolid_dispositivos_importados')->insert($insert);
            }

            $importacao->update([
                'total_tags' => $tags,
                'total_rastreadores' => count($rows) - $tags,
                'resumo' => [
                    'com_placa' => $importacao->dispositivos()->whereNotNull('placa')->count(),
                    'com_sim' => $importacao->dispositivos()->whereNotNull('sim')->count(),
                    'com_iccid' => $importacao->dispositivos()->whereNotNull('iccid')->count(),
                ],
            ]);

            return $importacao->refresh();
        });
    }

    public function extractPlate(mixed $value): ?string
    {
        $value = mb_strtoupper((string) $value);

        if (! preg_match('/(?<![A-Z0-9])([A-Z]{3})[- ]?([0-9][A-Z0-9][0-9]{2})(?![A-Z0-9])/u', $value, $matches)) {
            return null;
        }

        return $matches[1].$matches[2];
    }

    private function plate(mixed $value): ?string
    {
        $value = preg_replace('/[^A-Z0-9]/', '', mb_strtoupper((string) $value)) ?? '';

        return preg_match('/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/', $value) ? $value : null;
    }

    private function digits(mixed $value): ?string
    {
        $value = preg_replace('/\D+/', '', (string) $value) ?? '';

        return $value !== '' ? $value : null;
    }

    private function text(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
