<?php

namespace App\Services\Tracksolid;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class TracksolidService
{
    public function __construct(private readonly ?string $baseUrl = null) {}

    public function token(): array
    {
        $response = $this->request('jimi.oauth.token.get', [
            'user_id' => $this->configuration('account'),
            'user_pwd_md5' => strtolower($this->configuration('password_md5')),
            'expires_in' => 7200,
        ]);

        $result = $response['result'] ?? null;

        if (! is_array($result) || blank($result['accessToken'] ?? null)) {
            throw new TracksolidException('A Tracksolid retornou um token invalido.');
        }

        return $result;
    }

    public function devices(string $accessToken): array
    {
        $response = $this->request('jimi.user.device.list', [
            'access_token' => $accessToken,
            'target' => $this->configuration('account'),
        ]);

        return $this->resultList($response);
    }

    public function deviceDetail(string $accessToken, string $imei): array
    {
        $response = $this->request('jimi.track.device.detail', [
            'access_token' => $accessToken,
            'IMEI' => trim($imei),
        ]);

        $result = $response['result'] ?? null;

        if (! is_array($result)) {
            throw new TracksolidException('A Tracksolid retornou detalhes invalidos para o IMEI informado.');
        }

        return $result;
    }

    public function request(string $method, array $parameters): array
    {
        $parameters = array_merge([
            'method' => $method,
            'timestamp' => CarbonImmutable::now('UTC')->format('Y-m-d H:i:s'),
            'app_key' => $this->configuration('app_key'),
            'sign_method' => 'md5',
            'v' => '1.0',
            'format' => 'json',
        ], $parameters);

        $parameters['sign'] = $this->signature($parameters);

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout((int) config('services.tracksolid.timeout', 30))
                ->post($this->resolvedBaseUrl(), $parameters);
        } catch (ConnectionException $exception) {
            throw new TracksolidException(
                'Nao foi possivel conectar ao no Tracksolid configurado.',
                previous: $exception,
            );
        }

        if ($response->failed()) {
            $message = trim((string) ($response->json('message') ?? ''));
            $suffix = $message === '' ? '' : ' '.$message;

            throw new TracksolidException('A Tracksolid retornou HTTP '.$response->status().'.'.$suffix);
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new TracksolidException('A Tracksolid retornou uma resposta que nao e JSON valido.');
        }

        if ((int) ($data['code'] ?? -1) !== 0) {
            $message = trim((string) ($data['message'] ?? 'erro sem descricao'));
            $code = (string) ($data['code'] ?? 'desconhecido');

            throw new TracksolidException("Tracksolid recusou {$method}: {$message} (codigo {$code}).");
        }

        return $data;
    }

    private function signature(array $parameters): string
    {
        unset($parameters['sign']);
        ksort($parameters, SORT_STRING);

        $content = '';

        foreach ($parameters as $name => $value) {
            $content .= $name.(string) $value;
        }

        $secret = $this->configuration('app_secret');

        return strtoupper(md5($secret.$content.$secret));
    }

    private function resolvedBaseUrl(): string
    {
        $baseUrl = trim((string) ($this->baseUrl ?: config('services.tracksolid.base_url')));

        if ($baseUrl === '' || str_contains($baseUrl, 'SEU-NO')) {
            throw new TracksolidException('URL do no Tracksolid nao configurada.');
        }

        if (! str_starts_with($baseUrl, 'https://')) {
            throw new TracksolidException('O diagnostico exige uma URL Tracksolid HTTPS.');
        }

        return $baseUrl;
    }

    private function configuration(string $key): string
    {
        $value = trim((string) config('services.tracksolid.'.$key));

        if ($value === '') {
            throw new TracksolidException("Configuracao Tracksolid ausente: {$key}.");
        }

        return $value;
    }

    private function resultList(array $response): array
    {
        $result = $response['result'] ?? [];

        if (isset($result['data']) && is_array($result['data'])) {
            $result = $result['data'];
        }

        if (! is_array($result)) {
            throw new TracksolidException('A Tracksolid retornou um inventario invalido.');
        }

        return array_values(array_filter($result, 'is_array'));
    }
}
