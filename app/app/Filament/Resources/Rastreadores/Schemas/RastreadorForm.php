<?php

namespace App\Filament\Resources\Rastreadores\Schemas;

use App\Models\Rastreador;
use App\Models\StatusRastreador;
use App\Models\Tecnico;
use App\Models\Veiculo;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class RastreadorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados do Rastreador')
                    ->schema([
                        Grid::make(12)->schema([
                            Select::make('cliente_id')
                                ->label('Cliente')
                                ->relationship('cliente', 'nome')
                                ->searchable()
                                ->preload()
                                ->disabled(fn (): bool => request()->filled('cliente_id'))
                                ->dehydrated(true)
                                ->required()
                                ->columnSpan(6),
                            DatePicker::make('data_instalacao')
                                ->label('Data de Instalacao')
                                ->displayFormat('d/m/Y')
                                ->native(false)
                                ->columnSpan(6),
                            TextInput::make('veiculo')
                                ->label('Veiculo')
                                ->required()
                                ->maxLength(50)
                                ->columnSpan(6),
                            TextInput::make('placa')
                                ->label('Placa')
                                ->required()
                                ->maxLength(50)
                                ->columnSpan(6),
                            TextInput::make('cor')
                                ->label('Cor')
                                ->required()
                                ->maxLength(50)
                                ->columnSpan(6),
                            TextInput::make('ano')
                                ->label('Ano')
                                ->required()
                                ->maxLength(50)
                                ->columnSpan(6),
                            Select::make('tipo_veiculo_id')
                                ->label('Tipo Veiculo')
                                ->relationship('tipoVeiculo', 'label')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->columnSpan(6),
                            Select::make('rastreador_id')
                                ->label('IMEI')
                                ->options(fn (?Veiculo $record): array => self::rastreadorOptions($record))
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function (Set $set, ?int $state): void {
                                    $rastreador = Rastreador::query()
                                        ->with('tecnico')
                                        ->find($state);

                                    $set('tecnico_instala_id', $rastreador?->tecnico_id);
                                    $set('instalador', $rastreador?->tecnico?->nome);
                                })
                                ->columnSpan(6),
                            Select::make('chip_id')
                                ->label('Numero Chip')
                                ->relationship('chip', 'iccid')
                                ->searchable()
                                ->preload()
                                ->columnSpan(4),
                            TextInput::make('login')
                                ->label('Login')
                                ->maxLength(50)
                                ->columnSpan(4),
                            TextInput::make('senha')
                                ->label('Senha')
                                ->maxLength(50)
                                ->columnSpan(4),
                            Textarea::make('observacao')
                                ->label('Observacao')
                                ->rows(2)
                                ->columnSpan(5),
                            TextInput::make('valor_instalacao')
                                ->label('Valor de Instalacao')
                                ->numeric()
                                ->columnSpan(3),
                            Select::make('tecnico_instala_id')
                                ->label('Instalador')
                                ->relationship('tecnicoInstala', 'nome')
                                ->disabled()
                                ->dehydrated(true)
                                ->columnSpan(4),
                            TextInput::make('instalador')
                                ->label('Instalador Nome')
                                ->hidden()
                                ->dehydrated(true),
                            DatePicker::make('data_retirada')
                                ->label('Data Retirada')
                                ->displayFormat('d/m/Y')
                                ->native(false)
                                ->required(fn (Get $get): bool => self::isStatusCancelado($get('status_rastreador_id')))
                                ->columnSpan(4),
                            Select::make('tecnico_remocao_id')
                                ->label('Tecnico Remocao')
                                ->relationship('tecnicoRemocao', 'nome')
                                ->searchable()
                                ->preload()
                                ->required(fn (Get $get): bool => self::isStatusCancelado($get('status_rastreador_id')))
                                ->columnSpan(4),
                            Select::make('status_rastreador_id')
                                ->label('Status Rastreador')
                                ->options(StatusRastreador::query()
                                    ->whereIn('label', ['Ativo', 'Cancelado', 'Disponivel'])
                                    ->orderBy('order')
                                    ->pluck('label', 'id'))
                                ->required()
                                ->live()
                                ->columnSpan(4),
                            TextInput::make('associado')
                                ->label('Associado / Cliente')
                                ->maxLength(500)
                                ->columnSpan(4),
                            TextInput::make('contato')
                                ->label('Contato')
                                ->maxLength(50)
                                ->columnSpan(4),
                        ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<int, string>
     */
    private static function rastreadorOptions(?Veiculo $record): array
    {
        $disponivelId = Veiculo::statusId('Disponivel');

        return Rastreador::query()
            ->with('tecnico')
            ->when($disponivelId !== null, function (Builder $query) use ($disponivelId, $record): void {
                $query->where(function (Builder $query) use ($disponivelId, $record): void {
                    $query->where('status_rastreador_id', $disponivelId);

                    if ($record?->rastreador_id !== null) {
                        $query->orWhere('id', $record->rastreador_id);
                    }
                });
            })
            ->orderBy('imei')
            ->get()
            ->mapWithKeys(fn (Rastreador $rastreador): array => [
                $rastreador->id => $rastreador->imei . ' (' . ($rastreador->tecnico?->nome ?? 'Sem tecnico') . ')',
            ])
            ->all();
    }

    private static function isStatusCancelado(mixed $statusId): bool
    {
        return (int) $statusId === (int) Veiculo::statusId('Cancelado');
    }
}
