<?php

namespace App\Services\OrdemServico;

use App\Models\OrdemServicoDisponibilidade;
use App\Models\Tecnico;
use App\Services\Whatsapp\WhatsappService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TecnicoAgendaPublicaService
{
    public function __construct(private readonly WhatsappService $whatsapp) {}

    public function tecnicoPorToken(string $token): Tecnico
    {
        abort_unless(strlen($token) === 64, 404);

        return Tecnico::query()->where('agenda_token_hash', hash('sha256', $token))->firstOrFail();
    }

    public function gerarToken(Tecnico $tecnico): string
    {
        if (filled($tecnico->agenda_token_credencial) && filled($tecnico->agenda_token_hash)) {
            return (string) $tecnico->agenda_token_credencial;
        }

        return DB::transaction(function () use ($tecnico): string {
            $tecnico = Tecnico::query()->lockForUpdate()->findOrFail($tecnico->id);
            if (filled($tecnico->agenda_token_credencial) && filled($tecnico->agenda_token_hash)) {
                return (string) $tecnico->agenda_token_credencial;
            }

            $token = Str::random(64);
            $tecnico->forceFill([
                'agenda_token_hash' => hash('sha256', $token),
                'agenda_token_credencial' => $token,
            ])->save();

            return $token;
        });
    }

    public function enviarLink(Tecnico $tecnico): void
    {
        $telefone = preg_replace('/\D+/', '', (string) $tecnico->telefone) ?? '';
        if (strlen($telefone) !== 11) {
            throw ValidationException::withMessages(['telefone' => 'O técnico precisa ter um telefone válido com 11 dígitos.']);
        }

        $token = $this->gerarToken($tecnico);
        $mensagem = implode("\n", [
            "Olá, {$tecnico->nome}! Tudo bem?",
            '',
            'Segue seu acesso pessoal à agenda de atendimentos da *Conectta Rastreamento*.',
            '',
            route('tecnicos.agenda', ['token' => $token]),
            '',
            'Por esse link você pode consultar, incluir e remover seus horários disponíveis.',
            'Este link é pessoal. Não compartilhe com outras pessoas.',
        ]);

        $this->whatsapp->enviarTexto('55'.$telefone, $mensagem, 'agenda-tecnico-'.$tecnico->id.'-'.Str::uuid());
    }

    public function excluir(OrdemServicoDisponibilidade $disponibilidade, Tecnico $tecnico): void
    {
        abort_unless((int) $disponibilidade->tecnico_id === (int) $tecnico->id, 404);
        if ($disponibilidade->ordens()->exists()) {
            throw ValidationException::withMessages(['disponibilidade' => 'Este período possui uma OS vinculada e não pode ser excluído.']);
        }

        $disponibilidade->delete();
    }
}
