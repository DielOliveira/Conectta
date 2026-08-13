<?php

namespace App\Services\OrdemServico;

use App\Models\OrdemServicoFoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class OrdemServicoFotoStorage
{
    public function armazenar(UploadedFile $foto, int $ordemId): string
    {
        return $foto->store("ordens-servico/{$ordemId}", 'local');
    }

    public function arquivar(OrdemServicoFoto $foto): bool
    {
        if ($this->remoto($foto->caminho)) {
            return false;
        }

        $disco = Storage::disk('local');
        if (! $disco->exists($foto->caminho)) {
            throw new RuntimeException("A foto {$foto->id} não foi encontrada no armazenamento local.");
        }

        $caminhoLocal = $disco->path($foto->caminho);
        $caminhoRemoto = $this->caminhoRemoto($foto->ordem_servico_id.'/'.basename($foto->caminho));
        if ($this->rclone(['copyto', $caminhoLocal, $caminhoRemoto])->failed()) {
            throw new RuntimeException("Não foi possível arquivar a foto {$foto->id} no Google Drive.");
        }

        DB::transaction(function () use ($foto, $caminhoRemoto): void {
            $atual = OrdemServicoFoto::query()->lockForUpdate()->findOrFail($foto->id);
            if ($this->remoto($atual->caminho)) {
                return;
            }
            $atual->update(['caminho' => $caminhoRemoto]);
        });

        if (! $disco->delete($foto->caminho)) {
            throw new RuntimeException("A foto {$foto->id} foi arquivada, mas o arquivo local não pôde ser removido.");
        }

        return true;
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
