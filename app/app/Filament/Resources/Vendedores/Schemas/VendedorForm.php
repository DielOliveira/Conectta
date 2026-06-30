<?php

namespace App\Filament\Resources\Vendedores\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VendedorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados do Vendedor')
                    ->schema([
                        Grid::make(12)->schema([
                            TextInput::make('nome')
                                ->label('Nome')
                                ->required()
                                ->maxLength(50)
                                ->columnSpan(12),
                        ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}