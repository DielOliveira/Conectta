<?php

namespace App\Services\Tracksolid;

use App\Models\Rastreador;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TracksolidDiagnosticService
{
    public function build(array $remoteDevices): array
    {
        $vehiclesByTracker = DB::table('veiculos')
            ->whereNotNull('rastreador_id')
            ->whereNull('data_exclusao')
            ->orderByDesc('id')
            ->get(['id', 'placa', 'veiculo', 'cliente_id', 'status_rastreador_id', 'rastreador_id'])
            ->unique('rastreador_id')
            ->keyBy('rastreador_id');

        $localDevices = Rastreador::query()
            ->with([
                'chip:id,numero_chip,iccid',
                'statusRastreador:id,label',
            ])
            ->with(['tecnico:id,nome'])
            ->get()
            ->map(fn (Rastreador $tracker): array => $this->localDevice(
                $tracker,
                $vehiclesByTracker->get($tracker->id),
            ));

        $localByImei = $localDevices
            ->filter(fn (array $device): bool => $device['imei'] !== '')
            ->groupBy('imei');
        $remoteNormalized = collect($remoteDevices)
            ->map(fn (array $device): array => $this->remoteDevice($device))
            ->filter(fn (array $device): bool => $device['imei'] !== '')
            ->values();
        $remoteByImei = $remoteNormalized->groupBy('imei');

        $onlyLocal = $localByImei->keys()->diff($remoteByImei->keys())->values();
        $onlyRemote = $remoteByImei->keys()->diff($localByImei->keys())->values();
        $common = $localByImei->keys()->intersect($remoteByImei->keys())->values();

        $divergences = $common
            ->map(fn (string $imei): ?array => $this->compare(
                $localByImei->get($imei)->first(),
                $remoteByImei->get($imei)->first(),
            ))
            ->filter()
            ->values();

        return [
            'generated_at' => now()->toIso8601String(),
            'summary' => [
                'local_records' => $localDevices->count(),
                'local_unique_imeis' => $localByImei->count(),
                'local_without_imei' => $localDevices->where('imei', '')->count(),
                'local_duplicate_imeis' => $localByImei->filter(fn (Collection $items): bool => $items->count() > 1)->count(),
                'tracksolid_records' => $remoteNormalized->count(),
                'tracksolid_unique_imeis' => $remoteByImei->count(),
                'tracksolid_duplicate_imeis' => $remoteByImei->filter(fn (Collection $items): bool => $items->count() > 1)->count(),
                'in_both' => $common->count(),
                'only_local' => $onlyLocal->count(),
                'only_tracksolid' => $onlyRemote->count(),
                'with_field_divergences' => $divergences->count(),
            ],
            'only_local' => $onlyLocal->all(),
            'only_tracksolid' => $onlyRemote->all(),
            'local_duplicate_imeis' => $localByImei
                ->filter(fn (Collection $items): bool => $items->count() > 1)
                ->map(fn (Collection $items): array => $items->pluck('id')->all())
                ->all(),
            'tracksolid_duplicate_imeis' => $remoteByImei
                ->filter(fn (Collection $items): bool => $items->count() > 1)
                ->map(fn (Collection $items): array => $items->all())
                ->all(),
            'divergences' => $divergences->all(),
            'tracksolid_inventory' => $remoteNormalized->all(),
        ];
    }

    private function localDevice(Rastreador $tracker, ?object $vehicle): array
    {
        return [
            'id' => $tracker->id,
            'imei' => $this->normalizeImei($tracker->imei),
            'model' => $this->normalizeText($tracker->modelo),
            'chip_number' => $this->normalizeDigits($tracker->chip?->numero_chip),
            'iccid' => $this->normalizeDigits($tracker->chip?->iccid),
            'plate' => $this->normalizeText($vehicle?->placa),
            'vehicle' => $this->normalizeText($vehicle?->veiculo),
            'client_id' => $vehicle?->cliente_id,
            'tracker_status' => $tracker->statusRastreador?->label,
            'technician' => $tracker->tecnico?->nome,
        ];
    }

    private function remoteDevice(array $device): array
    {
        return [
            'imei' => $this->normalizeImei($this->first($device, ['imei', 'IMEI', 'deviceImei'])),
            'model' => $this->normalizeText($this->first($device, ['mcType', 'model', 'deviceModel'])),
            'device_name' => $this->normalizeText($this->first($device, ['deviceName', 'device_name'])),
            'sim' => $this->normalizeDigits($this->first($device, ['sim', 'simCard', 'phone'])),
            'iccid' => $this->normalizeDigits($this->first($device, ['iccid', 'ICCID'])),
            'plate' => $this->normalizeText($this->first($device, ['plateNumber', 'vehiclePlate', 'carLicense', 'vehicle_number'])),
            'enabled' => $this->first($device, ['enabledFlag', 'enabled', 'deviceStatus']),
            'expiration' => $this->first($device, ['expiration', 'expireDate', 'expirationTime']),
            'raw' => $device,
        ];
    }

    private function compare(array $local, array $remote): ?array
    {
        $fields = [
            'model' => [$local['model'], $remote['model']],
            'iccid' => [$local['iccid'], $remote['iccid']],
            'plate' => [$local['plate'], $remote['plate']],
        ];
        $different = [];

        foreach ($fields as $field => [$localValue, $remoteValue]) {
            if ($localValue !== '' && $remoteValue !== '' && $localValue !== $remoteValue) {
                $different[$field] = ['local' => $localValue, 'tracksolid' => $remoteValue];
            }
        }

        if ($different === []) {
            return null;
        }

        return [
            'imei' => $local['imei'],
            'fields' => $different,
            'local_id' => $local['id'],
        ];
    }

    private function first(array $data, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                return $data[$key];
            }
        }

        return null;
    }

    private function normalizeImei(mixed $value): string
    {
        return $this->normalizeDigits($value);
    }

    private function normalizeDigits(mixed $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    private function normalizeText(mixed $value): string
    {
        return mb_strtoupper(trim((string) $value));
    }
}
