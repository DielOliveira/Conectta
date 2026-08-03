<?php

namespace App\Filament\Resources\OrdensServico\RelationManagers;

use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FotosRelationManager extends RelationManager
{
    protected static string $relationship = 'fotos';

    protected static ?string $title = 'Fotos do atendimento';

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('nome_original')->label('Arquivo'),
            TextColumn::make('tamanho')->label('Tamanho')->formatStateUsing(fn (int $state) => number_format($state / 1048576, 2, ',', '.').' MB'),
            TextColumn::make('created_at')->label('Enviada em')->dateTime('d/m/Y H:i'),
        ])->recordActions([
            Action::make('abrir')->label('Abrir')->icon(Heroicon::OutlinedPhoto)->url(fn ($record) => route('ordens-servico.tecnico.foto', [$this->getOwnerRecord()->token_credencial, $record]))->openUrlInNewTab()
                ->visible(fn () => filled($this->getOwnerRecord()->token_credencial)),
        ]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
