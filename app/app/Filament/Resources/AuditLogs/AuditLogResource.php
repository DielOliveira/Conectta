<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Models\AuditLog;
use App\Models\Permission;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $slug = 'auditoria';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentMagnifyingGlass;

    protected static string|UnitEnum|null $navigationGroup = 'Administrativo';

    protected static ?int $navigationSort = 8;

    protected static ?string $modelLabel = 'Auditoria';

    protected static ?string $pluralModelLabel = 'Auditoria';

    protected static ?string $navigationLabel = 'Auditoria';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->placeholder('Sistema')
                    ->searchable(),
                TextColumn::make('acao')
                    ->label('Acao')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('entidade_tipo')
                    ->label('Entidade')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('entidade_id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('descricao')
                    ->label('Descricao')
                    ->limit(90)
                    ->wrap()
                    ->searchable(),
            ])
            ->recordActions([
                Action::make('detalhes')
                    ->label('Detalhes')
                    ->icon(Heroicon::OutlinedDocumentMagnifyingGlass)
                    ->color('gray')
                    ->modalHeading('Detalhes da auditoria')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar')
                    ->modalWidth('7xl')
                    ->modalContent(fn (AuditLog $record) => view('filament.resources.audit-logs.details', [
                        'record' => $record->loadMissing('user'),
                        'alteracoes' => self::alteracoes($record),
                        'alteracoesTecnicas' => self::alteracoes($record, true),
                        'antes' => self::jsonFormatado($record->antes),
                        'depois' => self::jsonFormatado($record->depois),
                        'contexto' => self::jsonFormatado($record->contexto),
                    ])),
            ])
            ->filters([
                SelectFilter::make('acao')
                    ->label('Acao')
                    ->options(fn (): array => AuditLog::query()
                        ->whereNotNull('acao')
                        ->distinct()
                        ->orderBy('acao')
                        ->pluck('acao', 'acao')
                        ->all()),
                SelectFilter::make('entidade_tipo')
                    ->label('Entidade')
                    ->options(fn (): array => AuditLog::query()
                        ->whereNotNull('entidade_tipo')
                        ->distinct()
                        ->orderBy('entidade_tipo')
                        ->pluck('entidade_tipo', 'entidade_tipo')
                        ->all()),
                SelectFilter::make('user_id')
                    ->label('Usuario')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasPermission(Permission::COORDENADOR) ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
        ];
    }

    /**
     * @return array<int, array{campo: string, antes: string, depois: string, origem: string}>
     */
    private static function alteracoes(AuditLog $record, bool $tecnicas = false): array
    {
        $antes = is_array($record->antes) ? $record->antes : [];
        $depois = is_array($record->depois) ? $record->depois : [];
        $contexto = is_array($record->contexto) ? $record->contexto : [];
        $alteracoes = [];

        $campos = array_values(array_unique(array_merge(array_keys($antes), array_keys($depois))));

        foreach ($campos as $campo) {
            if (self::campoTecnico($campo) !== $tecnicas) {
                continue;
            }

            $valorAntes = $antes[$campo] ?? null;
            $valorDepois = $depois[$campo] ?? null;

            if (self::valoresIguais($campo, $valorAntes, $valorDepois)) {
                continue;
            }

            $alteracoes[] = [
                'campo' => self::labelCampo($campo),
                'antes' => self::valorLegivel($campo, $valorAntes),
                'depois' => self::valorLegivel($campo, $valorDepois),
                'origem' => 'Registro',
            ];
        }

        foreach (self::alteracoesDoContexto($contexto, $tecnicas) as $alteracao) {
            $alteracoes[] = $alteracao;
        }

        return $alteracoes;
    }

    /**
     * @param  array<string, mixed>  $contexto
     * @return array<int, array{campo: string, antes: string, depois: string, origem: string}>
     */
    private static function alteracoesDoContexto(array $contexto, bool $tecnicas): array
    {
        $alteracoes = [];

        foreach ($contexto as $campo => $valorAntes) {
            if (! str_ends_with((string) $campo, '_antes')) {
                continue;
            }

            $base = substr((string) $campo, 0, -6);
            $campoDepois = $base.'_depois';

            if (! array_key_exists($campoDepois, $contexto)) {
                continue;
            }

            if (self::campoTecnico($base) !== $tecnicas) {
                continue;
            }

            $valorDepois = $contexto[$campoDepois];

            if (self::valoresIguais($base, $valorAntes, $valorDepois)) {
                continue;
            }

            $alteracoes[] = [
                'campo' => self::labelCampo($base),
                'antes' => self::valorLegivel($base, $valorAntes),
                'depois' => self::valorLegivel($base, $valorDepois),
                'origem' => 'Contexto',
            ];
        }

        return $alteracoes;
    }

    private static function campoTecnico(string|int $campo): bool
    {
        return in_array((string) $campo, [
            'id',
            'created_at',
            'updated_at',
            'time_stamp',
            'remember_token',
            'password',
            'doc_token',
        ], true);
    }

    private static function valoresIguais(string|int $campo, mixed $antes, mixed $depois): bool
    {
        return self::valorComparavel($campo, $antes) === self::valorComparavel($campo, $depois);
    }

    private static function valorComparavel(string|int $campo, mixed $valor): string
    {
        if (is_array($valor)) {
            ksort($valor);

            return json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        }

        if ($valor === null || $valor === '') {
            return '';
        }

        if (is_bool($valor)) {
            return $valor ? '1' : '0';
        }

        $campo = (string) $campo;

        if (str_starts_with($campo, 'valor_') || str_starts_with($campo, 'total_') || in_array($campo, ['total', 'total_value'], true)) {
            return number_format((float) $valor, 2, '.', '');
        }

        if (str_contains($campo, 'data') || $campo === 'vencimento') {
            try {
                return CarbonImmutable::parse((string) $valor)->format('Y-m-d');
            } catch (\Throwable) {
                return (string) $valor;
            }
        }

        if (str_ends_with($campo, '_at') || $campo === 'time_stamp') {
            try {
                return CarbonImmutable::parse((string) $valor)->format('Y-m-d H:i:s');
            } catch (\Throwable) {
                return (string) $valor;
            }
        }

        return (string) $valor;
    }

    private static function labelCampo(string|int $campo): string
    {
        $campo = (string) $campo;

        $labels = [
            'acao' => 'Acao',
            'ano_referencia' => 'Ano referencia',
            'anotacoes' => 'Anotacoes',
            'cliente_id' => 'Cliente ID',
            'cpf_cnpj' => 'CPF/CNPJ',
            'data_adesao' => 'Data adesao',
            'data_exclusao' => 'Data exclusao',
            'data_instalacao' => 'Data instalacao',
            'data_lancamento' => 'Data lancamento',
            'data_retirada' => 'Data retirada',
            'dia_pagamento' => 'Dia de pagamento',
            'email_verified_at' => 'Email verificado em',
            'entidade_id' => 'Entidade ID',
            'entidade_tipo' => 'Entidade',
            'hash_id' => 'Hash ID',
            'is_admin' => 'Administrador',
            'is_baixado' => 'Baixado',
            'is_estoque' => 'Estoque',
            'is_spc' => 'SPC',
            'lancamento_id' => 'Lancamento ID',
            'linha_digitavel' => 'Linha digitavel',
            'link_boleto' => 'Link boleto',
            'link_checkout' => 'Link checkout',
            'mes_referencia' => 'Mes referencia',
            'numero_boleto' => 'Numero boleto',
            'numero_chip' => 'Numero chip',
            'pix_copia_cola' => 'PIX copia e cola',
            'replicar_pagamento' => 'Replicar pagamento',
            'status_cliente_id' => 'Status cliente ID',
            'status_contrato_id' => 'Status contrato ID',
            'status_rastreador_id' => 'Status rastreador ID',
            'tecnico_id' => 'Tecnico ID',
            'tecnico_instala_id' => 'Tecnico instalacao ID',
            'tecnico_remocao_id' => 'Tecnico remocao ID',
            'tipo_contrato_id' => 'Tipo contrato ID',
            'tipo_veiculo_id' => 'Tipo veiculo ID',
            'total' => 'Total',
            'total_antes' => 'Total antes',
            'total_depois' => 'Total depois',
            'total_value' => 'Valor total',
            'user_id' => 'Usuario ID',
            'valor_efetivado' => 'Valor efetivado',
            'valor_instalacao' => 'Valor instalacao',
            'valor_planejado' => 'Valor planejado',
            'vendedor_id' => 'Vendedor ID',
            'vencimento' => 'Vencimento',
            'permissions' => 'Permissoes',
        ];

        return $labels[$campo] ?? str($campo)->replace('_', ' ')->headline()->toString();
    }

    private static function valorLegivel(string|int $campo, mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return 'Vazio';
        }

        if (is_bool($valor)) {
            return $valor ? 'Sim' : 'Nao';
        }

        if (is_array($valor)) {
            if ($valor === []) {
                return 'Vazio';
            }

            return json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'Vazio';
        }

        $campo = (string) $campo;

        if (str_starts_with($campo, 'valor_') || str_starts_with($campo, 'total_') || in_array($campo, ['total', 'total_value'], true)) {
            return number_format((float) $valor, 2, ',', '.');
        }

        if (str_contains($campo, 'data') || str_ends_with($campo, '_at') || $campo === 'vencimento') {
            try {
                $data = CarbonImmutable::parse((string) $valor);

                return str_contains((string) $valor, ':')
                    ? $data->format('d/m/Y H:i:s')
                    : $data->format('d/m/Y');
            } catch (\Throwable) {
                return (string) $valor;
            }
        }

        return (string) $valor;
    }

    private static function jsonFormatado(mixed $state): string
    {
        if ($state === null || $state === '' || $state === []) {
            return '';
        }

        if (is_string($state)) {
            return $state;
        }

        return json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }
}
