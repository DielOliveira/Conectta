<?php

namespace App\Filament\Resources\OrdensServico\RelationManagers;

use App\Models\OrdemServicoNotificacao;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MensagensGeradasRelationManager extends RelationManager
{
    protected static string $relationship = 'notificacoes';

    protected static ?string $title = 'Histórico de mensagens';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Gerada em')
                    ->dateTime('d/m/Y H:i:s')
                    ->description(fn (OrdemServicoNotificacao $record): string => $record->enviada_em
                        ? 'Enviada em '.$record->enviada_em->format('d/m/Y H:i:s')
                        : 'Ainda não enviada')
                    ->sortable(),
                TextColumn::make('destinatario_tipo')
                    ->label('Destinatário')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'tecnico' => 'Técnico',
                        'cliente' => 'Cliente',
                        default => ucfirst($state),
                    })
                    ->description(fn (OrdemServicoNotificacao $record): ?string => $record->telefone)
                    ->badge(),
                TextColumn::make('evento')
                    ->label('Evento')
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),
                TextColumn::make('mensagem')
                    ->label('Mensagem')
                    ->limit(80)
                    ->tooltip(fn (OrdemServicoNotificacao $record): string => $record->mensagem)
                    ->wrap()
                    ->width('18rem'),
                TextColumn::make('link_tecnico')
                    ->label('Acesso')
                    ->state(fn (OrdemServicoNotificacao $record): ?string => self::linkEnviadoAoTecnico($record))
                    ->formatStateUsing(fn (): string => 'Copiar link')
                    ->icon(Heroicon::OutlinedClipboard)
                    ->color('gray')
                    ->badge()
                    ->copyable(fn (?string $state): bool => filled($state))
                    ->copyableState(fn (?string $state): ?string => $state)
                    ->copyMessage('Link copiado')
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label('Situação')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pendente' => 'Pendente',
                        'enfileirada' => 'Enfileirada',
                        'enviada' => 'Enviada',
                        'erro' => 'Erro',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'enviada' => 'success',
                        'erro' => 'danger',
                        default => 'warning',
                    })
                    ->description(fn (OrdemServicoNotificacao $record): ?string => filled($record->erro)
                        ? str($record->erro)->limit(60)->toString()
                        : null)
                    ->tooltip(fn (OrdemServicoNotificacao $record): ?string => $record->erro)
                    ->badge(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    private static function linkEnviadoAoTecnico(OrdemServicoNotificacao $notificacao): ?string
    {
        if ($notificacao->destinatario_tipo !== 'tecnico') {
            return null;
        }

        preg_match('~https?://[^\s]+/os/[A-Za-z0-9]+~', $notificacao->mensagem, $links);

        return $links[0] ?? null;
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
