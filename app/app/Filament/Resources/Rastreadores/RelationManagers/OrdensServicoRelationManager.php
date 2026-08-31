<?php

namespace App\Filament\Resources\Rastreadores\RelationManagers;

use App\Filament\Resources\OrdensServico\OrdemServicoResource;
use App\Models\OrdemServico;
use App\Models\Permission;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class OrdensServicoRelationManager extends RelationManager
{
    protected static string $relationship = 'ordensServico';

    protected static ?string $title = 'Histórico de ordens de serviço';

    public function table(Table $table): Table
    {
        return $table
            ->description('Ordens vinculadas a esta placa, da mais recente para a mais antiga.')
            ->columns([
                TextColumn::make('numero')
                    ->label('OS')
                    ->formatStateUsing(fn (int|string $state): string => 'OS '.str_pad((string) $state, 6, '0', STR_PAD_LEFT))
                    ->sortable(),
                OrdemServicoResource::tipoTagColumn('Tipo de serviço'),
                OrdemServicoResource::statusTagColumn(),
                TextColumn::make('tecnico.nome')
                    ->label('Quem atendeu')
                    ->formatStateUsing(fn ($state, OrdemServico $record): string => $record->nome_tecnico_exibicao ?: 'Não atribuído')
                    ->placeholder('Não atribuído'),
                TextColumn::make('agendado_em')
                    ->label('Atendimento')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Não agendado')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('verOrdemServico')
                    ->label('Ver O.S.')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (OrdemServico $record): string => OrdemServicoResource::getUrl('edit', ['record' => $record])),
            ])
            ->emptyStateHeading('Nenhuma ordem de serviço vinculada')
            ->emptyStateDescription('Quando esta placa possuir uma O.S., ela aparecerá aqui.')
            ->defaultSort('numero', 'desc')
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5, 10, 25]);
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->hasPermission(Permission::OS_LEITURA) ?? false;
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
