<?php

namespace Tests\Unit;

use App\Models\Cliente;
use App\Models\OrdemServico;
use App\Models\OrdemServicoFoto;
use App\Models\StatusRastreador;
use App\Models\Veiculo;
use App\Services\OrdemServico\OrdemServicoFotoArquivoService;
use App\Services\OrdemServico\OrdemServicoFotoStorage;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrdemServicoFotoStorageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('ordens_servico.fotos.rclone_bin', '/usr/bin/rclone');
        config()->set('ordens_servico.fotos.rclone_config', '/etc/conectta/rclone.conf');
        config()->set('ordens_servico.fotos.rclone_remote', 'gdrive');
        config()->set('ordens_servico.fotos.rclone_base_path', 'Conectta/ordens-servico');
        StatusRastreador::query()->create(['label' => 'Disponivel', 'order' => 1, 'is_active' => true]);
        StatusRastreador::query()->create(['label' => 'Ativo', 'order' => 2, 'is_active' => true]);
        StatusRastreador::query()->create(['label' => 'Cancelado', 'order' => 3, 'is_active' => true]);
    }

    public function test_foto_nova_fica_local_e_depois_pode_ser_arquivada_no_drive(): void
    {
        Storage::fake('local');
        Process::fake(fn (PendingProcess $process) => ($process->command[3] ?? null) === 'cat'
            ? Process::result(output: 'conteudo-da-foto')
            : Process::result());
        $service = app(OrdemServicoFotoStorage::class);

        $caminhoLocal = $service->armazenar(
            UploadedFile::fake()->createWithContent('atendimento.jpg', 'conteudo-da-foto'),
            123,
        );

        $this->assertStringStartsWith('ordens-servico/123/', $caminhoLocal);
        Storage::disk('local')->assertExists($caminhoLocal);
        Process::assertNothingRan();

        $foto = $this->criarFotoFinalizada($caminhoLocal, CarbonImmutable::now()->subMonth()->subDay());
        $this->assertTrue($service->arquivar($foto));
        $caminhoRemoto = $foto->fresh()->caminho;

        $this->assertSame('gdrive:Conectta/ordens-servico/'.$foto->ordem_servico_id.'/'.basename($caminhoLocal), $caminhoRemoto);
        Storage::disk('local')->assertMissing($caminhoLocal);
        $this->assertSame('conteudo-da-foto', rtrim((string) $service->resposta($caminhoRemoto, 'atendimento.jpg', 'image/jpeg')->getContent(), "\n"));
        Process::assertRan(fn (PendingProcess $process): bool => ($process->command[3] ?? null) === 'copyto'
            && ($process->command[4] ?? null) === Storage::disk('local')->path($caminhoLocal)
            && ($process->command[5] ?? null) === $caminhoRemoto);
    }

    public function test_rotina_arquiva_apenas_foto_finalizada_ha_um_mes(): void
    {
        Storage::fake('local');
        Process::fake();
        $antiga = $this->criarFotoFinalizada('ordens-servico/1/antiga.jpg', CarbonImmutable::now()->subMonth()->subDay());
        $recente = $this->criarFotoFinalizada('ordens-servico/2/recente.jpg', CarbonImmutable::now()->subDays(20));
        Storage::disk('local')->put($antiga->caminho, 'antiga');
        Storage::disk('local')->put($recente->caminho, 'recente');

        $resultado = app(OrdemServicoFotoArquivoService::class)->processar();

        $this->assertSame(['processadas' => 1, 'arquivadas' => 1, 'erros' => 0], $resultado);
        $this->assertStringStartsWith('gdrive:', $antiga->fresh()->caminho);
        $this->assertSame('ordens-servico/2/recente.jpg', $recente->fresh()->caminho);
        Storage::disk('local')->assertExists('ordens-servico/2/recente.jpg');
    }

    public function test_falha_no_drive_preserva_arquivo_e_caminho_local(): void
    {
        Storage::fake('local');
        Process::fake([Process::result(errorOutput: 'drive indisponível', exitCode: 1)]);
        $foto = $this->criarFotoFinalizada('ordens-servico/3/preservada.jpg', CarbonImmutable::now()->subMonths(2));
        Storage::disk('local')->put($foto->caminho, 'preservada');

        $resultado = app(OrdemServicoFotoArquivoService::class)->processar();

        $this->assertSame(['processadas' => 1, 'arquivadas' => 0, 'erros' => 1], $resultado);
        $this->assertSame('ordens-servico/3/preservada.jpg', $foto->fresh()->caminho);
        Storage::disk('local')->assertExists('ordens-servico/3/preservada.jpg');
    }

    private function criarFotoFinalizada(string $caminho, CarbonImmutable $finalizadaEm): OrdemServicoFoto
    {
        $cliente = Cliente::query()->create(['nome' => 'Cliente Foto', 'cpf_cnpj' => fake()->unique()->numerify('###########'), 'data_adesao' => '2026-01-01', 'dia_pagamento' => 10]);
        $veiculo = Veiculo::query()->create(['cliente_id' => $cliente->id, 'veiculo' => 'Veículo Foto', 'placa' => fake()->unique()->bothify('???-####'),
            'status_rastreador_id' => StatusRastreador::query()->where('label', 'Disponivel')->value('id')]);
        $ordem = OrdemServico::query()->create([
            'numero' => OrdemServico::query()->max('numero') + 1,
            'tipo' => 'instalacao', 'status' => 'finalizada', 'cliente_id' => $cliente->id, 'veiculo_id' => $veiculo->id,
            'endereco' => 'Rua da Foto', 'descricao' => 'Teste de foto', 'finalizada_em' => $finalizadaEm,
        ]);

        return OrdemServicoFoto::query()->create([
            'ordem_servico_id' => $ordem->id, 'caminho' => $caminho,
            'nome_original' => basename($caminho), 'mime_type' => 'image/jpeg', 'tamanho' => 6,
        ]);
    }
}
