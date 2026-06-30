<?php

namespace App\Filament\Resources\Usuarios\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UsuarioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados do Usuario')
                    ->schema([
                        Grid::make(12)->schema([
                            TextInput::make('name')
                                ->label('Nome')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(6),
                            TextInput::make('email')
                                ->label('Email')
                                ->email()
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(255)
                                ->columnSpan(6),
                            TextInput::make('password')
                                ->label('Senha')
                                ->password()
                                ->revealable()
                                ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                                ->dehydrated(fn (?string $state): bool => filled($state))
                                ->required(fn (string $operation): bool => $operation === 'create')
                                ->maxLength(255)
                                ->columnSpan(6),
                            Toggle::make('is_admin')
                                ->label('Administrador')
                                ->helperText('Administrador acessa todas as telas e acoes, mesmo sem permissoes marcadas.')
                                ->columnSpan(6),
                        ]),
                    ])
                    ->columnSpanFull(),
                Section::make('Permissoes')
                    ->schema([
                        CheckboxList::make('permissions')
                            ->label('Permissoes')
                            ->relationship('permissions', 'label', fn ($query) => $query->orderBy('ordem')->orderBy('label'))
                            ->columns(2)
                            ->bulkToggleable()
                            ->helperText('Usuarios administradores ignoram esta lista. Para usuarios comuns, marque as permissoes desejadas.'),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}