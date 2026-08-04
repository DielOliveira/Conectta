<?php

namespace App\Services\OrdemServico;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class OrdemServicoFotoStorage
{
    public function armazenar(UploadedFile $foto, int $ordemId): string
    {
        if (! $this->usaGoogleDrive()) {
            return $foto->store("ordens-servico/{$ordemId}", 'local');
        }

        $extensao = strtolower($foto->guessExtension() ?: $foto->extension() ?: 'jpg');
        $caminho = $this->caminhoRemoto("{$ordemId}/".Str::random(40).".{$extensao}");
        $resultado = $this->rclone(['copyto', $foto->getRealPath(), $caminho]);

        if ($resultado->failed()) {
            throw new RuntimeException('Não foi possível salvar a foto no Google Drive. Tente novamente.');
        }

        return $caminho;
    }

    public function excluir(string $caminho): void
    {
        if (! $this->remoto($caminho)) {
            Storage::disk('local')->delete($caminho);

            return;
        }

        if ($this->rclone(['deletefile', $caminho])->failed()) {
            throw new RuntimeException('Não foi possível excluir a foto do Google Drive. Tente novamente.');
        }
    }

    public function resposta(string $caminho, ?string $nomeOriginal, string $mimeType): Response
    {
        if (! $this->remoto($caminho)) {
            return Storage::disk('local')->response($caminho, $nomeOriginal, ['Content-Type' => $mimeType]);
        }

        $resultado = $this->rclone(['cat', $caminho]);
        abort_if($resultado->failed(), 404);

        $nome = str_replace(['"', "\r", "\n"], '', basename((string) $nomeOriginal)) ?: 'foto';

        return response($resultado->output(), 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.$nome.'"',
            'Content-Length' => (string) strlen($resultado->output()),
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    private function usaGoogleDrive(): bool
    {
        return config('ordens_servico.fotos.driver') === 'rclone';
    }

    private function remoto(string $caminho): bool
    {
        return str_starts_with($caminho, $this->remote().':');
    }

    private function caminhoRemoto(string $sufixo): string
    {
        $base = trim((string) config('ordens_servico.fotos.rclone_base_path'), '/');

        return $this->remote().':'.$base.'/'.ltrim($sufixo, '/');
    }

    private function remote(): string
    {
        return rtrim((string) config('ordens_servico.fotos.rclone_remote'), ':');
    }

    private function rclone(array $argumentos)
    {
        return Process::timeout((int) config('ordens_servico.fotos.timeout', 60))
            ->run([
                (string) config('ordens_servico.fotos.rclone_bin', '/usr/bin/rclone'),
                '--config',
                (string) config('ordens_servico.fotos.rclone_config', '/etc/conectta/rclone.conf'),
                ...$argumentos,
            ]);
    }
}
