<?php

namespace App\Filament\Resources\Rastreadores\Schemas;

use App\Models\Chip;
use App\Models\Pais;
use App\Models\Rastreador;
use App\Models\StatusRastreador;
use App\Models\Veiculo;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class RastreadorForm
{
    /**
     * Campos de vínculo e movimentação cuja origem exclusiva é a ordem de serviço.
     *
     * @var array<int, string>
     */
    public const CAMPOS_GERENCIADOS_PELA_OS = [
        'rastreador_id',
        'chip_id_form',
        'tecnico_instala_id',
        'instalador',
        'tecnico_remocao_id',
        'data_retirada',
        'status_rastreador_id',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados do Rastreador')
                    ->description('IMEI, chip, instalador, técnico e data de remoção e status do rastreador são atualizados exclusivamente por ordem de serviço.')
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
                                ->rules([
                                    fn (?Veiculo $record): \Closure => function (string $attribute, mixed $value, \Closure $fail) use ($record): void {
                                        if ($record !== null && Veiculo::normalizarPlaca($record->placa) === Veiculo::normalizarPlaca((string) $value)) {
                                            return;
                                        }

                                        if (Veiculo::placaJaCadastrada((string) $value, $record?->id)) {
                                            $fail('Esta placa já está cadastrada em outro veículo.');
                                        }
                                    },
                                ])
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
                                ->disabled()
                                ->dehydrated(false)
                                ->columnSpan(6),
                            Select::make('chip_id_form')
                                ->label('Numero Chip')
                                ->default(fn (?Veiculo $record): ?int => self::chipId($record?->rastreador_id))
                                ->formatStateUsing(fn (mixed $state, ?Veiculo $record): ?int => filled($state) ? (int) $state : self::chipId($record?->rastreador_id))
                                ->native(false)
                                ->getOptionLabelUsing(fn (mixed $value): ?string => self::chipOptionLabel($value))
                                ->disabled()
                                ->dehydrated(false)
                                ->helperText(fn (Get $get): ?HtmlString => filled($get('rastreador_id')) && blank($get('chip_id_form'))
                                    ? new HtmlString('<span class="font-medium text-warning-600 dark:text-warning-400">O rastreador selecionado nao possui chip vinculado.</span>')
                                    : null)
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
                                ->dehydrated(false)
                                ->columnSpan(4),
                            TextInput::make('instalador')
                                ->label('Instalador Nome')
                                ->hidden()
                                ->disabled()
                                ->dehydrated(false),
                            DatePicker::make('data_retirada')
                                ->label('Data Retirada')
                                ->displayFormat('d/m/Y')
                                ->native(false)
                                ->disabled()
                                ->dehydrated(false)
                                ->columnSpan(4),
                            Select::make('tecnico_remocao_id')
                                ->label('Tecnico Remocao')
                                ->relationship('tecnicoRemocao', 'nome')
                                ->disabled()
                                ->dehydrated(false)
                                ->columnSpan(4),
                            Select::make('status_rastreador_id')
                                ->label('Status Rastreador')
                                ->options(StatusRastreador::query()
                                    ->whereIn('label', ['Ativo', 'Cancelado', 'Disponivel'])
                                    ->orderBy('order')
                                    ->pluck('label', 'id'))
                                ->disabled()
                                ->dehydrated(false)
                                ->columnSpan(4),
                            TextInput::make('associado')
                                ->label('Associado / Cliente')
                                ->maxLength(500)
                                ->columnSpan(4),
                            Select::make('contato_pais')
                                ->label('DDI do contato')
                                ->options(fn (): array => Pais::telefoneOptions())
                                ->default('BR')
                                ->formatStateUsing(fn ($state): string => Pais::normalizarCodigoTelefone($state) ?? 'BR')
                                ->dehydrateStateUsing(fn ($state): string => Pais::normalizarCodigoTelefone($state) ?? 'BR')
                                ->searchable()
                                ->preload()
                                ->live()
                                ->required()
                                ->columnSpan(3),
                            TextInput::make('contato')
                                ->label('Contato')
                                ->placeholder(fn (Get $get): string => $get('contato_pais') === 'BR' ? '(62) 9.9999-9999' : 'Número sem código do país')
                                ->mask(fn (Get $get): ?string => $get('contato_pais') === 'BR' ? '(99) 9.9999-9999' : null)
                                ->dehydrateStateUsing(fn (?string $state): ?string => ($digits = preg_replace('/\D+/', '', $state ?? '')) !== '' ? $digits : null)
                                ->formatStateUsing(fn ($state): ?string => self::formatTelefone($state))
                                ->maxLength(50)
                                ->columnSpan(5),
                        ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Defesa no servidor contra estados Livewire manipulados fora do formulário.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function removerCamposGerenciadosPelaOs(array $data): array
    {
        foreach (self::CAMPOS_GERENCIADOS_PELA_OS as $campo) {
            unset($data[$campo]);
        }

        return $data;
    }

    private static function formatTelefone(mixed $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        return strlen($digits) === 11
            ? preg_replace('/(\d{2})(\d)(\d{4})(\d{4})/', '($1) $2.$3-$4', $digits)
            : (blank($value) ? null : (string) $value);
    }

    /**
     * @return array<int, string>
     */
    private static function rastreadorOptions(?Veiculo $record): array
    {
        if ($record?->rastreador_id === null) {
            return [];
        }

        return Rastreador::query()
            ->whereKey($record->rastreador_id)
            ->pluck('imei', 'id')
            ->all();
    }

    private static function chipId(mixed $rastreadorId): ?int
    {
        if (blank($rastreadorId)) {
            return null;
        }

        $chipId = Rastreador::query()
            ->find((int) $rastreadorId)
            ?->chip_id;

        return $chipId === null ? null : (int) $chipId;
    }

    private static function chipOptionLabel(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $chip = Chip::query()
            ->with('rastreador:id,imei,chip_id')
            ->find((int) $value);

        return $chip ? self::chipLabel($chip) : null;
    }

    private static function chipLabel(Chip $chip): string
    {
        $label = (string) $chip->numero_chip;

        if (filled($chip->iccid)) {
            $label .= ' - '.$chip->iccid;
        }

        return $label;
    }
}
