<?php

namespace App\Services\ZapSign;

use App\Models\ConfiguracaoIntegracao;
use App\Models\Pais;
use App\Models\TipoContrato;
use App\Models\Veiculo;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class ZapSignService
{
    /**
     * @param  array<string, mixed>  $dados
     * @return array<string, mixed>
     */
    public function criarDocumento(Veiculo $veiculo, TipoContrato $tipoContrato, array $dados): array
    {
        $configuracao = ConfiguracaoIntegracao::zapsignAtiva();

        if (blank($configuracao->base_url) || blank($configuracao->token)) {
            throw new ZapSignException('Configuracao da ZapSign incompleta. Informe URL base e token em Administrativo > Integracoes.');
        }

        $templateId = $this->templateId($configuracao, $tipoContrato->label);

        if (blank($templateId)) {
            throw new ZapSignException('Template ZapSign nao configurado para contrato '.$tipoContrato->label.'.');
        }

        $payload = $this->payload($veiculo, $tipoContrato, $templateId, $dados);
        $baseUrl = rtrim((string) $configuracao->base_url, '/');

        try {
            $response = Http::baseUrl($baseUrl)
                ->acceptJson()
                ->asJson()
                ->timeout((int) ($configuracao->timeout ?: 30))
                ->withHeaders(['Authorization' => $this->authorizationHeader((string) $configuracao->token, $configuracao->auth_scheme)])
                ->post('/api/v1/models/create-doc/', $payload);
        } catch (ConnectionException $exception) {
            throw new ZapSignException('Nao foi possivel conectar na ZapSign: '.$exception->getMessage(), previous: $exception);
        }

        $body = $response->json();

        if (! $response->successful()) {
            throw new ZapSignException($this->mensagemErro($response->status(), $body));
        }

        if (! is_array($body)) {
            throw new ZapSignException('ZapSign retornou uma resposta invalida ao criar documento.');
        }

        return $body;
    }

    /**
     * @return array<string, mixed>
     */
    public function detalhesDocumento(string $token): array
    {
        $configuracao = ConfiguracaoIntegracao::zapsignAtiva();

        if (blank($configuracao->base_url) || blank($configuracao->token)) {
            throw new ZapSignException('Configuracao da ZapSign incompleta. Informe URL base e token em Administrativo > Integracoes.');
        }

        $baseUrl = rtrim((string) $configuracao->base_url, '/');

        try {
            $response = Http::baseUrl($baseUrl)
                ->acceptJson()
                ->timeout((int) ($configuracao->timeout ?: 30))
                ->withHeaders(['Authorization' => $this->authorizationHeader((string) $configuracao->token, $configuracao->auth_scheme)])
                ->get('/api/v1/docs/'.$token.'/');
        } catch (ConnectionException $exception) {
            throw new ZapSignException('Nao foi possivel conectar na ZapSign: '.$exception->getMessage(), previous: $exception);
        }

        $body = $response->json();

        if (! $response->successful()) {
            throw new ZapSignException($this->mensagemErroConsulta($response->status(), $body));
        }

        if (! is_array($body)) {
            throw new ZapSignException('ZapSign retornou uma resposta invalida ao consultar documento.');
        }

        return $body;
    }

    private function templateId(ConfiguracaoIntegracao $configuracao, string $tipo): ?string
    {
        return match ($tipo) {
            'Principal' => $configuracao->template_principal_id,
            'Aditivo' => $configuracao->template_aditivo_id,
            'Comodato' => $configuracao->template_comodato_id,
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $dados
     * @return array<string, mixed>
     */
    private function payload(Veiculo $veiculo, TipoContrato $tipoContrato, string $templateId, array $dados): array
    {
        $veiculo->loadMissing(['cliente.estado', 'tecnicoInstala']);
        $cliente = $veiculo->cliente;
        [$telefonePais, $telefone] = $this->telefonePaisNumero($cliente?->telefone1_pais, $cliente?->telefone1);
        $cpfCnpj = $this->digits($cliente?->cpf_cnpj);

        if ($tipoContrato->label === 'Comodato') {
            $signerName = (string) ($dados['comodato_empresa'] ?? $cliente?->nome ?? '');
            $signerEmail = (string) ($dados['comodato_email'] ?? $cliente?->email ?? '');
            [$signerPhonePais, $signerPhone] = $this->telefonePaisNumero($cliente?->telefone1_pais, $dados['comodato_telefone'] ?? $cliente?->telefone1);

            return [
                'template_id' => $templateId,
                'signer_name' => $signerName,
                'signer_email' => $signerEmail,
                'send_automatic_email' => true,
                'lang' => 'pt-br',
                'data' => [
                    ['de' => '{{CONTRATADA}}', 'para' => $signerName],
                    ['de' => '{{CNPJ}}', 'para' => $this->digits($dados['comodato_cnpj'] ?? $cpfCnpj)],
                    ['de' => '{{CONTRATANTE}}', 'para' => (string) ($dados['comodato_contratante'] ?? '')],
                    ['de' => '{{EMAIL}}', 'para' => $signerEmail],
                    ['de' => '{{VEICULO}}', 'para' => (string) ($dados['comodato_veiculo'] ?? $veiculo->veiculo)],
                    ['de' => '{{PLACA}}', 'para' => (string) ($dados['comodato_placa'] ?? $veiculo->placa)],
                    ['de' => '{{DATA}}', 'para' => (string) ($dados['comodato_data_instalacao'] ?? '')],
                    ['de' => '{{CPF}}', 'para' => $this->digits($dados['comodato_cpf'] ?? '')],
                    ['de' => '{{TELEFONE}}', 'para' => $signerPhone],
                    ['de' => '{{DATAHOJE}}', 'para' => now()->format('d/m/Y')],
                    ['de' => '{{TECNICO}}', 'para' => (string) ($dados['comodato_tecnico'] ?? $veiculo->tecnicoInstala?->nome)],
                ],
                'external_id' => (string) $veiculo->id,
                'signer_phone_country' => $signerPhonePais,
                'signer_phone_number' => $signerPhone,
            ];
        }

        $endereco = collect([$cliente?->rua, $cliente?->numero, $cliente?->setor])->filter()->implode(' ');

        return [
            'template_id' => $templateId,
            'signer_name' => (string) $cliente?->nome,
            'signer_email' => (string) $cliente?->email,
            'send_automatic_email' => true,
            'lang' => 'pt-br',
            'data' => [
                ['de' => '{{NOME}}', 'para' => (string) $cliente?->nome],
                ['de' => '{{CPF}}', 'para' => $cpfCnpj],
                ['de' => '{{ENDEREÃƒâ€¡O}}', 'para' => $endereco],
                ['de' => '{{CEP}}', 'para' => $this->digits($cliente?->cep)],
                ['de' => '{{CIDADE}}', 'para' => (string) $cliente?->cidade],
                ['de' => '{{UF}}', 'para' => (string) $cliente?->estado?->label],
                ['de' => '{{EMAIL}}', 'para' => (string) $cliente?->email],
                ['de' => '{{VEICULO}}', 'para' => (string) $veiculo->veiculo],
                ['de' => '{{PLACA}}', 'para' => (string) $veiculo->placa],
                ['de' => '{{DATA}}', 'para' => $veiculo->data_instalacao?->format('Y-m-d') ?? ''],
                ['de' => '{{VALORINSTALACAO}}', 'para' => (string) ((float) ($veiculo->valor_instalacao ?? 0))],
                ['de' => '{{DATAVENCIMENTO}}', 'para' => (string) $cliente?->dia_pagamento],
                ['de' => '{{VALORMENSAL}}', 'para' => (string) ($dados['valor_mensal'] ?? '0')],
                ['de' => '{{CONTATO}}', 'para' => $telefone],
                ['de' => '{{DATAHOJE}}', 'para' => now()->format('d/m/Y')],
            ],
            'external_id' => (string) $veiculo->id,
            'signer_phone_country' => $telefonePais,
            'signer_phone_number' => $telefone,
        ];
    }

    private function authorizationHeader(string $token, ?string $authScheme): string
    {
        if (str_contains($token, ' ')) {
            return $token;
        }

        $scheme = trim((string) $authScheme);

        return $scheme === '' ? $token : $scheme.' '.$token;
    }

    private function digits(mixed $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function telefonePaisNumero(mixed $pais, mixed $telefone): array
    {
        $pais = Pais::codigoTelefone(Pais::normalizarCodigoTelefone((string) $pais) ?? (string) $pais);
        $numero = $this->digits($telefone);

        if ($numero !== '' && str_starts_with($numero, $pais) && strlen($numero) > 11) {
            $numero = substr($numero, strlen($pais));
        }

        return [$pais, $numero];
    }

    private function mensagemErroConsulta(int $status, mixed $body): string
    {
        $message = is_array($body)
            ? ($body['message'] ?? $body['detail'] ?? $body['error'] ?? null)
            : null;

        if (is_string($message) && trim($message) !== '') {
            $message = trim($message);

            if (str_contains($message, 'API token not found') || str_contains($message, 'Token da API')) {
                return 'Token da ZapSign nao encontrado ou invalido. Confira o token do ambiente ativo nas Integracoes.';
            }

            return 'ZapSign recusou a consulta do documento: '.$message;
        }

        return match ($status) {
            400 => 'ZapSign recusou a consulta do documento por dados invalidos.',
            401, 403 => 'ZapSign recusou a consulta do documento por falha de autenticacao.',
            404 => 'Documento da ZapSign nao encontrado.',
            default => 'ZapSign retornou erro ao consultar documento. Codigo HTTP: '.$status.'.',
        };
    }

    private function mensagemErro(int $status, mixed $body): string
    {
        $message = is_array($body)
            ? ($body['message'] ?? $body['detail'] ?? $body['error'] ?? null)
            : null;

        if (is_string($message) && trim($message) !== '') {
            $message = trim($message);

            if (str_contains($message, 'API token not found') || str_contains($message, 'Token da API')) {
                return 'Token da ZapSign nao encontrado ou invalido. Confira o token do ambiente ativo nas Integracoes.';
            }

            return 'ZapSign recusou a criacao do documento: '.$message;
        }

        return match ($status) {
            400 => 'ZapSign recusou a criacao do documento por dados invalidos.',
            401, 403 => 'ZapSign recusou a criacao do documento por falha de autenticacao.',
            404 => 'Endpoint da ZapSign nao encontrado.',
            default => 'ZapSign retornou erro ao criar documento. Codigo HTTP: '.$status.'.',
        };
    }
}
