<?php

namespace App\Filament\Resources\Clientes\Schemas;

use App\Rules\CpfCnpj;
use Carbon\CarbonInterface;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClienteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados Gerais')
                    ->schema([
                        Grid::make(12)->schema([
                            TextInput::make('nome')
                                ->label('Nome')
                                ->required()
                                ->maxLength(100)
                                ->columnSpan(6),
                            DatePicker::make('data_adesao')
                                ->label('Data de adesao')
                                ->validationAttribute('data de adesao')
                                ->displayFormat('d/m/Y')
                                ->native(false)
                                ->required()
                                ->columnSpan(4),
                            Checkbox::make('is_spc')
                                ->label('Cliente no SPC')
                                ->columnSpan(2),
                            TextInput::make('cpf_cnpj')
                                ->label('CPF CNPJ')
                                ->validationAttribute('CPF/CNPJ')
                                ->required()
                                ->maxLength(50)
                                ->rules([new CpfCnpj()])
                                ->unique(ignoreRecord: true)
                                ->dehydrateStateUsing(fn (?string $state): string => preg_replace('/\D+/', '', $state ?? ''))
                                ->columnSpan(6),
                            TextInput::make('rg')
                                ->label('RG')
                                ->maxLength(50)
                                ->columnSpan(6),
                            TextInput::make('nascimento')
                                ->label('Nascimento')
                                ->placeholder('dd/mm/aaaa')
                                ->mask('99/99/9999')
                                ->dehydrateStateUsing(function (?string $state): ?string {
                                    if (blank($state)) {
                                        return null;
                                    }

                                    $date = \DateTime::createFromFormat('d/m/Y', $state);

                                    return $date?->format('Y-m-d');
                                })
                                ->formatStateUsing(function ($state): ?string {
                                    if (blank($state)) {
                                        return null;
                                    }

                                    if ($state instanceof CarbonInterface || $state instanceof \DateTimeInterface) {
                                        return $state->format('d/m/Y');
                                    }

                                    $date = \DateTime::createFromFormat('Y-m-d', (string) $state)
                                        ?: \DateTime::createFromFormat('Y-m-d H:i:s', (string) $state)
                                        ?: \DateTime::createFromFormat('d/m/Y', (string) $state);

                                    return $date instanceof \DateTimeInterface
                                        ? $date->format('d/m/Y')
                                        : (string) $state;
                                })
                                ->columnSpan(3),
                            TextInput::make('email')
                                ->label('Email')
                                ->validationAttribute('email')
                                ->email()
                                ->rules(['nullable', 'email:rfc'])
                                ->maxLength(250)
                                ->columnSpan(3),
                            Select::make('cliente_origem_id')
                                ->label('Origem')
                                ->relationship('origem', 'label')
                                ->searchable()
                                ->preload()
                                ->columnSpan(6),
                        ]),
                    ])
                    ->columnSpanFull(),

                Section::make('Endereco')
                    ->schema([
                        Grid::make(12)->schema([
                            TextInput::make('rua')
                                ->label('Logradouro')
                                ->maxLength(150)
                                ->columnSpan(6),
                            TextInput::make('complemento')
                                ->label('Complemento')
                                ->maxLength(50)
                                ->columnSpan(6),
                            TextInput::make('numero')
                                ->label('Numero')
                                ->maxLength(50)
                                ->columnSpan(4),
                            TextInput::make('setor')
                                ->label('Setor')
                                ->maxLength(50)
                                ->columnSpan(4),
                            TextInput::make('cidade')
                                ->label('Cidade')
                                ->maxLength(50)
                                ->columnSpan(4),
                            Select::make('estado_id')
                                ->label('Estado')
                                ->relationship('estado', 'label')
                                ->searchable()
                                ->preload()
                                ->columnSpan(6),
                            TextInput::make('cep')
                                ->label('CEP')
                                ->placeholder('00.000-000')
                                ->mask('99.999-999')
                                ->dehydrateStateUsing(fn (?string $state): ?string => self::onlyDigitsOrNull($state))
                                ->formatStateUsing(fn ($state): ?string => self::formatCep($state))
                                ->maxLength(50)
                                ->columnSpan(6),
                        ]),
                    ])
                    ->columnSpanFull(),

                Section::make('Contato')
                    ->schema([
                        Grid::make(12)->schema([
                            TextInput::make('telefone1')
                                ->label('Telefone Celular')
                                ->validationAttribute('telefone celular')
                                ->placeholder('(00) 0.0000-0000')
                                ->mask('(99) 9.9999-9999')
                                ->dehydrateStateUsing(fn (?string $state): string => self::onlyDigitsOrNull($state) ?? '')
                                ->formatStateUsing(fn ($state): ?string => self::formatTelefoneCelular($state))
                                ->required()
                                ->maxLength(50)
                                ->columnSpan(6),
                            TextInput::make('telefone2')
                                ->label('Telefone Secundario')
                                ->maxLength(50)
                                ->columnSpan(6),
                        ]),
                    ])
                    ->columnSpanFull(),

                Section::make('Comercial')
                    ->schema([
                        Grid::make(12)->schema([
                            TextInput::make('empresa')
                                ->label('Empresa')
                                ->maxLength(50)
                                ->columnSpan(6),
                            Select::make('vendedor_id')
                                ->label('Vendedor')
                                ->relationship('vendedor', 'nome')
                                ->searchable()
                                ->preload()
                                ->columnSpan(6),
                            TextInput::make('indicacao')
                                ->label('Indicacao')
                                ->maxLength(50)
                                ->columnSpan(4),
                            Select::make('status_contrato_id')
                                ->label('Status Contrato')
                                ->relationship('statusContrato', 'label')
                                ->searchable()
                                ->preload()
                                ->columnSpan(4),
                            Select::make('dia_pagamento')
                                ->label('Dia de Pagamento')
                                ->validationAttribute('dia de pagamento')
                                ->options(array_combine(range(1, 31), range(1, 31)))
                                ->required()
                                ->columnSpan(4),
                        ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private static function onlyDigitsOrNull(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', $value ?? '');

        return $digits === '' ? null : $digits;
    }

    private static function formatCep(mixed $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        if (strlen($digits) !== 8) {
            return blank($value) ? null : (string) $value;
        }

        return preg_replace('/(\d{2})(\d{3})(\d{3})/', '$1.$2-$3', $digits);
    }

    private static function formatTelefoneCelular(mixed $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        if (strlen($digits) !== 11) {
            return blank($value) ? null : (string) $value;
        }

        return preg_replace('/(\d{2})(\d)(\d{4})(\d{4})/', '($1) $2.$3-$4', $digits);
    }
}
