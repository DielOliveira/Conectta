<?php

namespace App\Filament\Resources\Tecnicos\Schemas;

use App\Rules\Cpf;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TecnicoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados do Tecnico')
                    ->schema([
                        Grid::make(12)->schema([
                            TextInput::make('nome')
                                ->label('Nome')
                                ->required()
                                ->maxLength(50)
                                ->columnSpan(6),
                            TextInput::make('cpf')
                                ->label('CPF')
                                ->rules(['nullable', new Cpf()])
                                ->maxLength(50)
                                ->columnSpan(3),
                            TextInput::make('telefone')
                                ->label('Telefone')
                                ->maxLength(50)
                                ->columnSpan(3),
                            Checkbox::make('is_ativo')
                                ->label('Ativo')
                                ->default(true)
                                ->columnSpan(3),
                        ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
