<?php

namespace App\Filament\Resources\Tecnicos\Schemas;

use App\Rules\Cpf;
use App\Support\ChipNumber;
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
                                ->rules(['nullable', new Cpf])
                                ->maxLength(50)
                                ->columnSpan(3),
                            TextInput::make('telefone')
                                ->label('Telefone')
                                ->prefix('+55')
                                ->mask('(99) 99999-9999')
                                ->stripCharacters(['(', ')', ' ', '-'])
                                ->formatStateUsing(fn (?string $state): string => ChipNumber::local($state))
                                ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? ChipNumber::local($state) : null)
                                ->regex(ChipNumber::LOCAL_REGEX)
                                ->validationMessages([
                                    'regex' => 'Informe um celular brasileiro válido, com DDD.',
                                ])
                                ->maxLength(15)
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
