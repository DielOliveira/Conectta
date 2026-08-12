<?php

namespace App\Filament\Resources\Tecnicos\Tables;

use App\Filament\Resources\Tecnicos\Pages\ListTecnicos;
use App\Filament\Resources\Tecnicos\TecnicoResource;
use App\Models\Tecnico;
use App\Services\OrdemServico\TecnicoAgendaPublicaService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class TecnicosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->header(view('filament.resources.tecnicos.table-toolbar-filters'))
            ->columns([
                TextColumn::make('nome')
                    ->label('Nome')
                    ->sortable(),
                TextColumn::make('cpf')
                    ->label('CPF')
                    ->formatStateUsing(fn (?string $state): string => Tecnico::formatarCpf($state))
                    ->sortable(),
                TextColumn::make('telefone')
                    ->label('Telefone'),
                IconColumn::make('is_ativo')
                    ->label('Ativo')
                    ->boolean()
                    ->sortable(),
            ])
            ->modifyQueryUsing(fn (Builder $query, ListTecnicos $livewire): Builder => $livewire->aplicarFiltrosTecnicos($query))
            ->recordActions([
                Action::make('enviarLinkAgenda')
                    ->label('Enviar Link')
                    ->icon(Heroicon::PaperAirplane)
                    ->color('success')
                    ->visible(fn (Tecnico $record): bool => TecnicoResource::podeManter() && filled($record->telefone))
                    ->requiresConfirmation()
                    ->modalHeading('Enviar acesso à agenda')
                    ->modalDescription(fn (Tecnico $record): string => "Enviar pelo WhatsApp o link pessoal da agenda para {$record->nome}?")
                    ->modalSubmitActionLabel('Enviar Link')
                    ->action(function (Tecnico $record): void {
                        try {
                            app(TecnicoAgendaPublicaService::class)->enviarLink($record);
                            Notification::make()->title('Link da agenda enviado ao técnico.')->success()->send();
                        } catch (ValidationException $exception) {
                            Notification::make()->title('Não foi possível enviar o link.')->body(collect($exception->errors())->flatten()->first())->danger()->send();
                        } catch (\Throwable $exception) {
                            report($exception);
                            Notification::make()->title('Não foi possível enviar o link.')->body('Verifique a integração do WhatsApp e tente novamente.')->danger()->send();
                        }
                    }),
                EditAction::make()
                    ->label('Editar')
                    ->visible(fn (): bool => TecnicoResource::podeManter()),
                DeleteAction::make()
                    ->label('Excluir')
                    ->visible(fn (): bool => TecnicoResource::podeManter())
                    ->modalSubmitActionLabel('Excluir')
                    ->requiresConfirmation()
                    ->modalDescription('Deseja excluir este tecnico?'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => TecnicoResource::podeManter())
                        ->label('Excluir selecionados')
                        ->modalSubmitActionLabel('Excluir')
                        ->requiresConfirmation()
                        ->modalDescription('Deseja excluir os tecnicos selecionados?'),
                ]),
            ])
            ->defaultSort('nome');
    }
}
