<?php

namespace App\Filament\Resources\CobrancaMensagemModelos;

use App\Filament\Resources\CobrancaMensagemModelos\Pages\CreateCobrancaMensagemModelo;
use App\Filament\Resources\CobrancaMensagemModelos\Pages\EditCobrancaMensagemModelo;
use App\Filament\Resources\CobrancaMensagemModelos\Pages\ListCobrancaMensagemModelos;
use App\Models\CobrancaMensagemModelo;
use App\Models\Permission;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class CobrancaMensagemModeloResource extends Resource
{
    protected static ?string $model = CobrancaMensagemModelo::class;

    protected static ?string $slug = 'modelos-mensagem-cobranca';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Rotinas';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Modelo de mensagem';

    protected static ?string $pluralModelLabel = 'Modelos de mensagem';

    protected static ?string $navigationLabel = 'Mensagens de cobranca';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Mensagem')
                ->schema([
                    Grid::make(12)->schema([
                        TextInput::make('nome')
                            ->label('Nome')
                            ->required()
                            ->maxLength(120)
                            ->columnSpan(6),
                        Select::make('tipo')
                            ->label('Tipo')
                            ->required()
                            ->options(self::tipos())
                            ->searchable()
                            ->columnSpan(4),
                        TextInput::make('ordem')
                            ->label('Ordem')
                            ->numeric()
                            ->default(10)
                            ->required()
                            ->columnSpan(1),
                        Toggle::make('ativo')
                            ->label('Ativo')
                            ->default(true)
                            ->columnSpan(1),
                        Textarea::make('conteudo')
                            ->label('Conteudo')
                            ->required()
                            ->rows(12)
                            ->helperText('Variaveis: {cliente_nome}, {valor}, {vencimento}, {dias_atraso}, {mes}, {ano}.')
                            ->columnSpan(12),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nome')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->formatStateUsing(fn (string $state): string => self::tipos()[$state] ?? $state)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ordem')
                    ->label('Ordem')
                    ->sortable(),
                IconColumn::make('ativo')
                    ->label('Ativo')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()->label('Editar'),
            ])
            ->defaultSort('tipo');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasPermission(Permission::FINANCEIRO_LEITURA) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasPermission(Permission::FINANCEIRO_ESCRITA) ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->hasPermission(Permission::FINANCEIRO_ESCRITA) ?? false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCobrancaMensagemModelos::route('/'),
            'create' => CreateCobrancaMensagemModelo::route('/create'),
            'edit' => EditCobrancaMensagemModelo::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function tipos(): array
    {
        return [
            'boleto_7_dias' => 'Boleto 7 dias antes',
            'lembrete_vencimento' => 'Lembrete no vencimento',
            'atraso_2' => 'Atraso 2 dias',
            'atraso_5' => 'Atraso 5 dias',
            'atraso_7' => 'Atraso 7 dias',
            'atraso_10' => 'Atraso 10 dias',
            'atraso_12' => 'Atraso 12 dias',
            'atraso_15' => 'Atraso 15 dias',
            'pix_instrucao' => 'Instrucao PIX',
            'finalizacao' => 'Finalizacao',
        ];
    }
}
