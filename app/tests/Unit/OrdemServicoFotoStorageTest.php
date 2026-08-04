<?php

namespace Tests\Unit;

use App\Services\OrdemServico\OrdemServicoFotoStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class OrdemServicoFotoStorageTest extends TestCase
{
    public function test_armazena_exibe_e_exclui_foto_pelo_rclone(): void
    {
        config()->set('ordens_servico.fotos.driver', 'rclone');
        config()->set('ordens_servico.fotos.rclone_bin', '/usr/bin/rclone');
        config()->set('ordens_servico.fotos.rclone_config', '/etc/conectta/rclone.conf');
        config()->set('ordens_servico.fotos.rclone_remote', 'gdrive');
        config()->set('ordens_servico.fotos.rclone_base_path', 'Conectta/ordens-servico');

        Process::fake(function (PendingProcess $process) {
            return ($process->command[3] ?? null) === 'cat'
                ? Process::result(output: 'conteudo-da-foto')
                : Process::result();
        });

        $service = app(OrdemServicoFotoStorage::class);
        $caminho = $service->armazenar(
            UploadedFile::fake()->createWithContent('atendimento.jpg', 'conteudo-da-foto'),
            123,
        );

        $this->assertMatchesRegularExpression(
            '#^gdrive:Conectta/ordens-servico/123/[A-Za-z0-9]{40}\.jpg$#',
            $caminho,
        );

        $resposta = $service->resposta($caminho, 'atendimento.jpg', 'image/jpeg');
        $this->assertSame('conteudo-da-foto', rtrim((string) $resposta->getContent(), "\n"));
        $this->assertSame('image/jpeg', $resposta->headers->get('Content-Type'));

        $service->excluir($caminho);

        Process::assertRan(fn (PendingProcess $process): bool => ($process->command[3] ?? null) === 'copyto'
            && str_starts_with((string) ($process->command[5] ?? ''), 'gdrive:Conectta/ordens-servico/123/'));
        Process::assertRan(fn (PendingProcess $process): bool => $process->command === ['/usr/bin/rclone', '--config', '/etc/conectta/rclone.conf', 'cat', $caminho]);
        Process::assertRan(fn (PendingProcess $process): bool => $process->command === ['/usr/bin/rclone', '--config', '/etc/conectta/rclone.conf', 'deletefile', $caminho]);
    }
}
