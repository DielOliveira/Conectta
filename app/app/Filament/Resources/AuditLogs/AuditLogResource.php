<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Models\AuditLog;
use BackedEnum;
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
        return auth()->user()?->isAdmin() ?? false;
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

    private static function jsonResumo(mixed $state): string
    {
        if ($state === null || $state === '') {
            return '';
        }

        if (is_string($state)) {
            return $state;
        }

        return json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
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
