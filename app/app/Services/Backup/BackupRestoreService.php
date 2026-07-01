<?php

namespace App\Services\Backup;

use App\Models\Pais;
use Carbon\Carbon;
use Database\Seeders\PaisSeeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class BackupRestoreService
{
    public function __construct(private readonly XlsxTableReader $reader) {}

    /**
     * @param  array<string, string>  $files
     * @return array<string, mixed>
     */
    public function restore(array $files, bool $limparBase = true): array
    {
        $this->validarArquivos($files);

        $inicio = microtime(true);
        $summary = [
            'tabelas' => [],
            'avisos' => [],
            'duracao_segundos' => 0,
        ];

        DB::disableQueryLog();

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            if ($limparBase) {
                $this->limparBase();
            }

            $summary['tabelas']['paises'] = $this->garantirPaises();
            $summary['tabelas']['vendedores'] = $this->importVendedores($files['vendedores']);
            $summary['tabelas']['tecnicos'] = $this->importTecnicos($files['tecnicos']);
            $summary['tabelas']['rastreadores'] = $this->importRastreadores($files['rastreadores']);
            $summary['tabelas']['clientes'] = $this->importClientes($files['clientes'], $summary['avisos']);
            $summary['tabelas']['veiculos'] = $this->importVeiculos($files['veiculos']);
            $summary['tabelas']['contratos'] = $this->importContratos($files['veiculos']);
            $summary['tabelas']['lancamentos'] = $this->importLancamentos($files['lancamentos']);
            $summary['tabelas']['invoices'] = $this->importInvoices($files['invoices']);
            $summary['tabelas']['faturamentos'] = $this->importFaturamentos($files['faturamentos']);

            $this->ajustarAutoIncrement([
                'vendedores', 'tecnicos', 'rastreadores', 'clientes', 'veiculos', 'chips', 'contratos', 'lancamentos', 'invoices', 'faturamentos',
            ]);

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } catch (Throwable $exception) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            throw $exception;
        }

        $summary['duracao_segundos'] = round(microtime(true) - $inicio, 2);

        return $summary;
    }

    /**
     * @param  array<string, string>  $files
     */
    private function validarArquivos(array $files): void
    {
        foreach (['clientes', 'veiculos', 'rastreadores', 'tecnicos', 'vendedores', 'lancamentos', 'invoices', 'faturamentos'] as $key) {
            if (blank($files[$key] ?? null) || ! is_file($files[$key])) {
                throw new RuntimeException('Arquivo obrigatorio nao encontrado: '.$key.'.');
            }
        }
    }

    private function limparBase(): void
    {
        foreach (['contratos', 'invoices', 'lancamentos', 'veiculos', 'chips', 'rastreadores', 'clientes', 'tecnicos', 'vendedores', 'faturamentos'] as $table) {
            DB::table($table)->truncate();
        }
    }

    private function garantirPaises(): int
    {
        app(PaisSeeder::class)->run();

        return Pais::query()->count();
    }

    private function importVendedores(string $path): int
    {
        $rows = [];
        foreach ($this->reader->rows($path) as $row) {
            $rows[] = [
                'id' => $this->int($row['Id'] ?? null),
                'numr' => $this->int($row['NUMR'] ?? null),
                'nome' => $this->text($row['Nome'] ?? null, 50) ?: 'Sem nome',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $this->insertChunked('vendedores', $rows);
    }

    private function importTecnicos(string $path): int
    {
        $rows = [];
        foreach ($this->reader->rows($path) as $row) {
            $rows[] = [
                'id' => $this->int($row['Id'] ?? null),
                'nome' => $this->text($row['Nome'] ?? null, 50) ?: 'Sem nome',
                'cpf' => $this->digits($row['CPF'] ?? null) ?: null,
                'telefone' => $this->digits($row['Telefone'] ?? null) ?: null,
                'is_ativo' => $this->bool($row['Is Ativo'] ?? true),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $this->insertChunked('tecnicos', $rows);
    }

    private function importRastreadores(string $path): int
    {
        $rows = [];
        foreach ($this->reader->rows($path) as $row) {
            $rows[] = [
                'id' => $this->int($row['Id'] ?? null),
                'modelo' => $this->text($row['Modelo'] ?? null, 50),
                'ativacao' => $this->int($row['Ativacao'] ?? null),
                'imei' => $this->text($row['IMEI'] ?? null, 50) ?: 'SEM-IMEI-'.$this->int($row['Id'] ?? random_int(100000, 999999)),
                'tecnico_id' => $this->fk('tecnicos', $row['Tecnico'] ?? null),
                'is_estoque' => $this->bool($row['Is Estoque'] ?? true),
                'status_rastreador_id' => $this->fk('status_rastreadores', $row['Status Rastreador'] ?? null),
                'criado_em' => $this->dateTime($row['Criado Em'] ?? null),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $this->insertChunked('rastreadores', $rows);
    }

    /**
     * @param  array<int, string>  $avisos
     */
    private function importClientes(string $path, array &$avisos): int
    {
        $rows = [];
        $cpfs = [];
        foreach ($this->reader->rows($path) as $row) {
            $id = $this->int($row['Id'] ?? null);
            $cpf = $this->digits($row['CPF CNPJ'] ?? null);

            if ($cpf === '') {
                $cpf = 'IMPORTADO-'.$id;
                $avisos[] = 'Cliente '.$id.' estava sem CPF/CNPJ.';
            } elseif (isset($cpfs[$cpf])) {
                $cpf .= '-DUP-'.$id;
                $avisos[] = 'Cliente '.$id.' tinha CPF/CNPJ duplicado no backup e recebeu sufixo tecnico.';
            }

            $cpfs[$this->digits($row['CPF CNPJ'] ?? null)] = true;

            $rows[] = [
                'id' => $id,
                'numr' => $this->int($row['NUMR'] ?? null),
                'vendedor_id' => $this->fk('vendedores', $row['Vendedor'] ?? null),
                'status_contrato_id' => $this->fk('status_contratos', $row['Status Contrato'] ?? null),
                'status_cliente_id' => $this->fk('status_clientes', $row['Status Cliente'] ?? null),
                'cliente_origem_id' => $this->fk('cliente_origens', $row['Cliente Origem'] ?? null),
                'estado_id' => $this->fk('estados', $row['Estado'] ?? null),
                'nome' => $this->text($row['Nome'] ?? null, 100) ?: 'Sem nome',
                'cpf_cnpj' => $cpf,
                'rg' => $this->text($row['RG'] ?? null, 50),
                'nascimento' => $this->date($row['Nascimento'] ?? null),
                'email' => $this->text($row['Email'] ?? null, 250),
                'cep' => $this->digits($row['CEP'] ?? null) ?: null,
                'rua' => $this->text($row['RUA'] ?? null, 150),
                'numero' => $this->text($row['Numero'] ?? null, 50),
                'complemento' => $this->text($row['Complemento'] ?? null, 50),
                'setor' => $this->text($row['Setor'] ?? null, 50),
                'cidade' => $this->text($row['Cidade'] ?? null, 50),
                'telefone1' => $this->digits($row['Telefone 1'] ?? null) ?: '00000000000',
                'telefone1_pais' => 'BR',
                'telefone2' => $this->digits($row['Telefone 2'] ?? null) ?: null,
                'telefone2_pais' => blank($this->digits($row['Telefone 2'] ?? null)) ? null : 'BR',
                'empresa' => $this->text($row['Empresa'] ?? null, 50),
                'indicacao' => $this->text($row['Indicacao'] ?? null, 50),
                'data_adesao' => $this->date($row['Data Adesao'] ?? null) ?? now()->toDateString(),
                'data_exclusao' => $this->dateTime($row['Dataexclusao'] ?? null),
                'dia_pagamento' => max(1, min(31, $this->int($row['Dia Pagamento'] ?? 1) ?: 1)),
                'is_spc' => $this->bool($row['is SPC'] ?? false),
                'anotacoes' => $this->text($row['Anotacoes'] ?? null, 65535),
                'replicar_pagamento' => $this->bool($row['Replicar Pagamento'] ?? false),
                'created_at' => $this->dateTime($row['Created At'] ?? null) ?? now(),
                'updated_at' => $this->dateTime($row['Updated At'] ?? null) ?? now(),
            ];
        }

        return $this->insertChunked('clientes', $rows);
    }

    private function importVeiculos(string $path): int
    {
        $rows = [];
        foreach ($this->reader->rows($path) as $row) {
            $chipId = $this->chipId($row['Numero Chip'] ?? null, $row['Instalador'] ?? null);

            $rows[] = [
                'id' => $this->int($row['Id'] ?? null),
                'cliente_id' => $this->fk('clientes', $row['Cliente'] ?? null),
                'status_rastreador_id' => $this->fk('status_rastreadores', $row['Status Rastreador'] ?? null),
                'veiculo' => $this->text($row['Veiculo'] ?? null, 50),
                'placa' => $this->text($row['Placa'] ?? null, 50),
                'imei' => null,
                'data_instalacao' => $this->date($row['Data Instalacao'] ?? null),
                'data_retirada' => $this->date($row['Data Retirada'] ?? null),
                'data_exclusao' => $this->dateTime($row['Data Exclusao'] ?? null),
                'tipo_veiculo_id' => $this->fk('tipo_veiculos', $row['Tipo Veiculo'] ?? null),
                'rastreador_id' => $this->fk('rastreadores', $row['Rastreador'] ?? null),
                'chip_id' => $chipId,
                'tecnico_instala_id' => $this->tecnicoIdPorNome($row['Instalador'] ?? null),
                'tecnico_remocao_id' => $this->fk('tecnicos', $row['Tecnico Remocao(2)'] ?? null),
                'cor' => $this->text($row['Cor'] ?? null, 50),
                'ano' => $this->text($row['Ano'] ?? null, 50),
                'login' => $this->text($row['Login'] ?? null, 50),
                'senha' => $this->text($row['Senha'] ?? null, 50),
                'tecnico_remocao' => $this->text($row['Tecnico Remocao'] ?? null, 50),
                'instalador' => $this->text($row['Instalador'] ?? null, 50),
                'valor_instalacao' => $this->decimal($row['Valor Instalacao'] ?? null),
                'associado' => $this->text($row['Associado'] ?? null, 500),
                'contato' => $this->text($row['Contato'] ?? null, 50),
                'observacao' => $this->text($row['Observacao'] ?? null, 65535),
                'created_at' => $this->dateTime($row['Created At'] ?? null) ?? now(),
                'updated_at' => $this->dateTime($row['Updated At'] ?? null) ?? now(),
            ];
        }

        return $this->insertChunked('veiculos', $rows);
    }

    private function importContratos(string $path): int
    {
        $rows = [];
        foreach ($this->reader->rows($path) as $row) {
            $veiculoId = $this->fk('veiculos', $row['Id'] ?? null);
            if (! $veiculoId) {
                continue;
            }

            $tipoOriginal = $this->text($row['Contrato Tipo'] ?? null, 50);
            $token = $this->text($row['doc token'] ?? null, 255);
            $statusContratoId = $this->fk('status_contratos', $row['Status Contrato'] ?? null);
            $statusContratoOriginal = $this->int($row['Status Contrato'] ?? null);

            if (blank($tipoOriginal) && blank($token) && $statusContratoOriginal === 4) {
                continue;
            }

            if (blank($tipoOriginal) && blank($token) && ! $statusContratoId) {
                continue;
            }

            $tipoContratoId = $this->tipoContratoId($tipoOriginal ?: 'Principal');
            if (! $tipoContratoId) {
                continue;
            }

            $rows[] = [
                'veiculo_id' => $veiculoId,
                'tipo_contrato_id' => $tipoContratoId,
                'status_contrato_id' => $statusContratoId,
                'doc_token' => $token,
                'created_at' => $this->dateTime($row['Created At'] ?? null) ?? now(),
                'updated_at' => $this->dateTime($row['Updated At'] ?? null) ?? now(),
            ];
        }

        return $this->insertChunked('contratos', $rows);
    }

    private function importLancamentos(string $path): int
    {
        $rows = [];
        foreach ($this->reader->rows($path) as $row) {
            if (! $this->fk('clientes', $row['Cliente'] ?? null)) {
                continue;
            }

            $rows[] = [
                'id' => $this->int($row['Id'] ?? null),
                'numr' => $this->int($row['NUMR'] ?? null),
                'cliente_id' => $this->fk('clientes', $row['Cliente'] ?? null),
                'data_lancamento' => $this->date($row['Data Lancamento'] ?? null),
                'valor_planejado' => $this->decimal($row['Valor Planejado'] ?? null),
                'valor_efetivado' => $this->decimal($row['Valor Efetivado'] ?? null),
                'numero_boleto' => $this->text($row['Numero Boleto'] ?? null, 500),
                'observacao' => $this->text($row['Observacao'] ?? null, 65535),
                'is_baixado' => $this->bool($row['is Baixado'] ?? false),
                'mes_referencia' => $this->int($row['Mes Referencia'] ?? null),
                'ano_referencia' => $this->int($row['Ano Referencia'] ?? null),
                'time_stamp' => $this->dateTime($row['Time Stamp'] ?? null),
                'log' => $this->text($row['Log'] ?? null, 65535),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $this->insertChunked('lancamentos', $rows);
    }

    private function importInvoices(string $path): int
    {
        $rows = [];
        foreach ($this->reader->rows($path) as $row) {
            $lancamentoId = $this->fk('lancamentos', $row['Lancamentos'] ?? null);
            if (! $lancamentoId) {
                continue;
            }

            $rows[] = [
                'id' => $this->int($row['Id'] ?? null),
                'client_id' => $this->text($row['Client'] ?? null, 50),
                'cpf_cnpj' => $this->digits($row['CPFCNPJ'] ?? null) ?: null,
                'fatura_id' => $this->text($row['Fatura'] ?? null, 100),
                'total_value' => $this->decimal($row['total Value'] ?? null),
                'created_at_external' => $this->text($row['created At'] ?? null, 50),
                'updated_at_external' => $this->text($row['updated At'] ?? null, 50),
                'hash_id' => $this->text($row['hash'] ?? null, 500),
                'lancamento_id' => $lancamentoId,
                'status' => $this->text($row['Status'] ?? null, 50),
                'vencimento' => $this->dateTime($row['Vencimento'] ?? null),
                'user_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $this->insertChunked('invoices', $rows);
    }

    private function importFaturamentos(string $path): int
    {
        $rows = [];
        foreach ($this->reader->rows($path) as $row) {
            $rows[] = [
                'id' => $this->int($row['Id'] ?? null),
                'ano' => $this->int($row['Ano'] ?? null),
                'mes' => $this->int($row['Mes'] ?? null),
                'is_aberto' => $this->bool($row['is Aberto'] ?? false),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $this->insertChunked('faturamentos', $rows);
    }

    private function tipoContratoId(?string $tipo): ?int
    {
        $tipo = trim((string) $tipo);

        $label = match (mb_strtolower($tipo)) {
            '1', 'principal' => 'Principal',
            '2', 'aditivo' => 'Aditivo',
            '3', 'comodato' => 'Comodato',
            default => $tipo,
        };

        if ($label === '') {
            return null;
        }

        return DB::table('tipo_contratos')->where('label', $label)->value('id') ?: null;
    }

    private function chipId(mixed $numeroChip, mixed $instalador): ?int
    {
        $iccid = $this->text($numeroChip, 50);
        if (blank($iccid)) {
            return null;
        }

        $existing = DB::table('chips')->where('iccid', $iccid)->value('id');
        if ($existing) {
            return (int) $existing;
        }

        return (int) DB::table('chips')->insertGetId([
            'fornecedor' => 'Importacao',
            'operadora' => null,
            'iccid' => $iccid,
            'tecnico_id' => $this->tecnicoIdPorNome($instalador),
            'status_rastreador_id' => DB::table('status_rastreadores')->where('label', 'Disponivel')->value('id') ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function tecnicoIdPorNome(mixed $nome): ?int
    {
        $nome = $this->text($nome, 50);
        if (blank($nome)) {
            return null;
        }

        return DB::table('tecnicos')->where('nome', $nome)->value('id') ?: null;
    }

    private function fk(string $table, mixed $id): ?int
    {
        $id = $this->int($id);
        if (! $id) {
            return null;
        }

        return DB::table($table)->where('id', $id)->exists() ? $id : null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function insertChunked(string $table, array $rows): int
    {
        $count = 0;
        foreach (array_chunk($rows, 500) as $chunk) {
            if ($chunk === []) {
                continue;
            }

            DB::table($table)->insert($chunk);
            $count += count($chunk);
        }

        return $count;
    }

    /**
     * @param  array<int, string>  $tables
     */
    private function ajustarAutoIncrement(array $tables): void
    {
        foreach ($tables as $table) {
            $max = (int) DB::table($table)->max('id');
            DB::statement('ALTER TABLE '.$table.' AUTO_INCREMENT = '.($max + 1));
        }
    }

    private function text(mixed $value, int $limit): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        if ($text === '' || $text === '-') {
            return null;
        }

        return mb_substr($text, 0, $limit);
    }

    private function digits(mixed $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    private function int(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function decimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return round((float) $value, 2);
        }

        $value = str_replace(['.', ','], ['', '.'], (string) $value);

        return is_numeric($value) ? round((float) $value, 2) : null;
    }

    private function bool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return ((int) $value) === 1;
        }

        return in_array(mb_strtolower(trim((string) $value)), ['true', 'sim', '1', 'yes'], true);
    }

    private function date(mixed $value): ?string
    {
        return $this->carbon($value)?->toDateString();
    }

    private function dateTime(mixed $value): ?string
    {
        return $this->carbon($value)?->toDateTimeString();
    }

    private function carbon(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::create(1899, 12, 30)->addDays((int) $value);
        }

        try {
            return Carbon::parse((string) $value);
        } catch (Throwable) {
            return null;
        }
    }
}
