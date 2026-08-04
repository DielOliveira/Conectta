<?php

namespace App\Filament\Resources\OrdensServico\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
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
                    ->sortable(),
                TextColumn::make('enviada_em')
                    ->label('Enviada em')
                    ->dateTime('d/m/Y H:i:s')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('destinatario_tipo')
                    ->label('Destinatário')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'tecnico' => 'Técnico',
                        'cliente' => 'Cliente',
                        default => ucfirst($state),
                    })
                    ->badge(),
                TextColumn::make('telefone')
                    ->label('Telefone')
                    ->copyable(false),
                TextColumn::make('evento')
                    ->label('Evento')
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),
                TextColumn::make('mensagem')
                    ->label('Mensagem')
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Situação')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pendente' => 'Pendente',
                        'enviada' => 'Enviada',
                        'erro' => 'Erro',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'enviada' => 'success',
                        'erro' => 'danger',
                        default => 'warning',
                    })
                    ->badge(),
                TextColumn::make('erro')
                    ->label('Erro')
                    ->placeholder('—')
                    ->wrap(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
