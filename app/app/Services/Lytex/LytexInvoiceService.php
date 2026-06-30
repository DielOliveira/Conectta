<?php

namespace App\Services\Lytex;

use App\Models\ConfiguracaoIntegracao;
use App\Models\TokenLytex;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class LytexInvoiceService
{
    public function criarFatura(array $payload): array
    {
        [$baseUrl, $authScheme, $timeout, $token] = $this->dadosAutenticados();

        try {
            $response = Http::baseUrl($baseUrl)
                ->acceptJson()
                ->asJson()
                ->timeout((int) $timeout)
                ->withHeaders([
                    'Authorization' => $this->authorizationHeader($token, $authScheme),
                ])
                ->post('/v2/invoices/', $payload);
        } catch (ConnectionException) {
            throw new LytexException('Nao foi possivel conectar com a Lytex. Tente novamente em instantes.');
        }

        if ($response->failed()) {
            throw new LytexException($this->mensagemErro($response->status(), $response->json()));
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new LytexException('A Lytex retornou uma resposta invalida.');
        }

        return $data;
    }

    public function cancelarFatura(string $faturaId): array
    {
        [$baseUrl, $authScheme, $timeout, $token] = $this->dadosAutenticados();

        try {
            $response = Http::baseUrl($baseUrl)
                ->acceptJson()
                ->timeout((int) $timeout)
                ->withHeaders([
                    'Authorization' => $this->authorizationHeader($token, $authScheme),
                ])
                ->put('/v2/invoices/cancel/' . $faturaId);
        } catch (ConnectionException) {
            throw new LytexException('Nao foi possivel cancelar o boleto na Lytex. Tente novamente em instantes.');
        }

        if ($response->failed()) {
            throw new LytexException($this->mensagemErroOperacao('cancelar boleto', $response->status(), $response->json()));
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new LytexException('A Lytex retornou uma resposta invalida ao cancelar boleto.');
        }

        return $data;
    }

    public function detalhesFatura(string $faturaId): array
    {
        [$baseUrl, $authScheme, $timeout, $token] = $this->dadosAutenticados();

        try {
            $response = Http::baseUrl($baseUrl)
                ->acceptJson()
                ->timeout((int) $timeout)
                ->withHeaders([
                    'Authorization' => $this->authorizationHeader($token, $authScheme),
                ])
                ->get('/v2/invoices/' . $faturaId);
        } catch (ConnectionException) {
            throw new LytexException('Nao foi possivel buscar os detalhes do boleto na Lytex. Tente novamente em instantes.');
        }

        if ($response->failed()) {
            throw new LytexException($this->mensagemErroOperacao('buscar detalhes do boleto', $response->status(), $response->json()));
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new LytexException('A Lytex retornou uma resposta invalida ao buscar detalhes do boleto.');
        }

        return $data;
    }

    private function dadosAutenticados(): array
    {
        $configuracao = ConfiguracaoIntegracao::lytex();

        if (! $configuracao->ativo) {
            throw new LytexException('Integracao Lytex desativada.');
        }

        $baseUrl = rtrim((string) ($configuracao->base_url ?: config('services.lytex.base_url')), '/');
        $authScheme = $configuracao->auth_scheme ?: config('services.lytex.auth_scheme', 'Bearer');
        $timeout = $configuracao->timeout ?: (int) config('services.lytex.timeout', 30);

        if ($baseUrl === '') {
            throw new LytexException('URL da Lytex nao configurada.');
        }

        $token = $this->accessToken($configuracao, $baseUrl, (int) $timeout);

        return [$baseUrl, $authScheme, $timeout, $token];
    }

    private function accessToken(ConfiguracaoIntegracao $configuracao, string $baseUrl, int $timeout): string
    {
        $tokenAtual = TokenLytex::query()
            ->where('configuracao_integracao_id', $configuracao->id)
            ->first();

        if ($tokenAtual?->access_token && $tokenAtual->expire_at?->isFuture()) {
            return $tokenAtual->access_token;
        }

        $clientId = trim((string) ($configuracao->client_id ?: config('services.lytex.client_id')));
        $clientSecret = trim((string) ($configuracao->client_secret ?: config('services.lytex.client_secret')));

        if ($clientId === '') {
            throw new LytexException('ClientId da Lytex nao configurado.');
        }

        if ($clientSecret === '') {
            throw new LytexException('ClientSecret da Lytex nao configurado.');
        }

        try {
            $response = Http::baseUrl($baseUrl)
                ->acceptJson()
                ->asJson()
                ->timeout($timeout)
                ->post('/v2/auth/obtain_token', [
                    'grantType' => 'clientCredentials',
                    'clientId' => $clientId,
                    'clientSecret' => $clientSecret,
                ]);
        } catch (ConnectionException) {
            throw new LytexException('Nao foi possivel obter o token da Lytex. Tente novamente em instantes.');
        }

        if ($response->failed()) {
            throw new LytexException($this->mensagemErroToken($response->status(), $response->json()));
        }

        $data = $response->json();

        if (! is_array($data) || blank($data['accessToken'] ?? null)) {
            throw new LytexException('A Lytex retornou um token invalido.');
        }

        TokenLytex::query()->updateOrCreate(
            ['configuracao_integracao_id' => $configuracao->id],
            [
                'access_token' => $data['accessToken'],
                'refresh_token' => $data['refreshToken'] ?? null,
                'expire_at' => $this->dataToken($data['expireAt'] ?? null),
                'refresh_expire_at' => $this->dataToken($data['refreshExpireAt'] ?? null),
            ],
        );

        return (string) $data['accessToken'];
    }

    private function authorizationHeader(string $token, ?string $authScheme): string
    {
        if (str_contains($token, ' ')) {
            return $token;
        }

        $scheme = trim((string) $authScheme);

        return $scheme === '' ? $token : $scheme . ' ' . $token;
    }

    private function mensagemErro(int $status, mixed $body): string
    {
        $message = $this->extrairMensagemErro($body);

        if ($message !== null) {
            return 'Lytex recusou a geracao do boleto: ' . $message;
        }

        return match ($status) {
            400 => 'Lytex recusou a geracao do boleto por dados invalidos.',
            401, 403 => 'Lytex recusou a geracao do boleto por falha de autenticacao.',
            404 => 'Endpoint da Lytex nao encontrado.',
            422 => 'Lytex recusou a geracao do boleto por validacao dos dados enviados.',
            default => 'Lytex retornou erro ao gerar boleto. Codigo HTTP: ' . $status . '.',
        };
    }

    private function mensagemErroOperacao(string $operacao, int $status, mixed $body): string
    {
        $message = $this->extrairMensagemErro($body);

        if ($message !== null) {
            return 'Lytex recusou ' . $operacao . ': ' . $message;
        }

        return match ($status) {
            400 => 'Lytex recusou ' . $operacao . ' por dados invalidos.',
            401, 403 => 'Lytex recusou ' . $operacao . ' por falha de autenticacao.',
            404 => 'Endpoint da Lytex nao encontrado para ' . $operacao . '.',
            422 => 'Lytex recusou ' . $operacao . ' por validacao dos dados enviados.',
            default => 'Lytex retornou erro ao ' . $operacao . '. Codigo HTTP: ' . $status . '.',
        };
    }

    private function mensagemErroToken(int $status, mixed $body): string
    {
        $message = $this->extrairMensagemErro($body);

        if ($message !== null) {
            return 'Lytex recusou a obtencao do token: ' . $message;
        }

        return match ($status) {
            400 => 'Lytex recusou a obtencao do token por dados invalidos.',
            401, 403 => 'Lytex recusou a obtencao do token por falha de autenticacao.',
            404 => 'Endpoint de token da Lytex nao encontrado.',
            default => 'Lytex retornou erro ao obter token. Codigo HTTP: ' . $status . '.',
        };
    }

    private function extrairMensagemErro(mixed $body): ?string
    {
        if (! is_array($body)) {
            return null;
        }

        $message = $body['error'][0]['message']
            ?? $body['errors'][0]['message']
            ?? $body['message']
            ?? $body['error']
            ?? null;

        if (! is_string($message) || trim($message) === '') {
            return null;
        }

        return $this->normalizarMensagemErro(trim($message));
    }

    private function normalizarMensagemErro(string $message): string
    {
        $message = str_replace('"dueDate"', 'A data de vencimento', $message);
        $message = str_replace('"client.cpfCnpj"', 'O CPF/CNPJ do cliente', $message);
        $message = str_replace('"client.email"', 'O email do cliente', $message);
        $message = str_replace('"client.cellphone"', 'O telefone celular do cliente', $message);

        $message = preg_replace_callback('/"(\d{4})-(\d{2})-(\d{2})T[^"]+"/', function (array $matches): string {
            return $matches[3] . '/' . $matches[2] . '/' . $matches[1];
        }, $message) ?? $message;

        return $message;
    }

    private function dataToken(?string $date): ?CarbonImmutable
    {
        if (blank($date)) {
            return null;
        }

        try {
            return CarbonImmutable::parse($date);
        } catch (\Throwable) {
            return null;
        }
    }
}
