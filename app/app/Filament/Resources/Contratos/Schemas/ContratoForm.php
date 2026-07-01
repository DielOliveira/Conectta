<?php

namespace App\Filament\Resources\Contratos\Schemas;

use App\Models\Cliente;
use App\Models\Pais;
use App\Models\StatusContrato;
use App\Models\TipoContrato;
use App\Models\Veiculo;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class ContratoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Dados do contrato')
                ->schema([
                    Grid::make(12)->schema([
                        Select::make('cliente_id')
                            ->label('Cliente')
                            ->options(fn (): array => Cliente::query()
                                ->whereNull('data_exclusao')
                                ->orderBy('nome')
                                ->pluck('nome', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->dehydrated(false)
                            ->afterStateUpdated(function (Set $set): void {
                                $set('veiculo_id', null);
                                $set('dados', []);
                            })
                            ->required()
                            ->columnSpan(6),
                        Select::make('veiculo_id')
                            ->label('Rastreador')
                            ->options(fn (Get $get): array => self::veiculoOptions((int) $get('cliente_id')))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Set $set, Get $get, mixed $state): mixed => self::preencherDadosPadrao($set, $get, $state))
                            ->required()
                            ->columnSpan(6),
                        Select::make('tipo_contrato_id')
                            ->label('Tipo de contrato')
                            ->options(fn (): array => TipoContrato::query()
                                ->where('is_active', true)
                                ->orderBy('order')
                                ->orderBy('label')
                                ->pluck('label', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get): void {
                                $set('dados', []);
                                self::preencherDadosPadrao($set, $get, $get('veiculo_id'));
                            })
                            ->required()
                            ->columnSpan(6),
                        Select::make('status_contrato_id')
                            ->label('Status')
                            ->options(fn (): array => StatusContrato::query()
                                ->where('is_active', true)
                                ->orderBy('order')
                                ->orderBy('label')
                                ->pluck('label', 'id')
                                ->all())
                            ->default(fn (): ?int => StatusContrato::query()->where('label', 'Nao Enviado')->value('id'))
                            ->disabled()
                            ->dehydrated(true)
                            ->required()
                            ->columnSpan(6),
                        Placeholder::make('preview_contrato')
                            ->label('Dados calculados')
                            ->content(fn (Get $get): HtmlString => new HtmlString(self::previewContrato((int) $get('veiculo_id'), self::tipoSelecionado($get('tipo_contrato_id')))))
                            ->visible(fn (Get $get): bool => filled($get('veiculo_id')) && filled($get('tipo_contrato_id')))
                            ->columnSpanFull(),
                        TextInput::make('dados.valor_mensal')
                            ->label('Valor mensal')
                            ->visible(fn (Get $get): bool => self::tipoSelecionado($get('tipo_contrato_id')) !== 'Comodato' && filled($get('tipo_contrato_id')))
                            ->required(fn (Get $get): bool => self::tipoSelecionado($get('tipo_contrato_id')) !== 'Comodato' && filled($get('tipo_contrato_id')))
                            ->maxLength(50)
                            ->columnSpan(6),
                        TextInput::make('dados.comodato_contratante')
                            ->label('Contratante')
                            ->visible(fn (Get $get): bool => self::tipoSelecionado($get('tipo_contrato_id')) === 'Comodato')
                            ->columnSpan(6),
                        TextInput::make('dados.comodato_cpf')
                            ->label('CPF')
                            ->visible(fn (Get $get): bool => self::tipoSelecionado($get('tipo_contrato_id')) === 'Comodato')
                            ->columnSpan(6),
                        TextInput::make('dados.comodato_email')
                            ->label('Email')
                            ->email()
                            ->visible(fn (Get $get): bool => self::tipoSelecionado($get('tipo_contrato_id')) === 'Comodato')
                            ->columnSpan(6),
                        TextInput::make('dados.comodato_telefone')
                            ->label('Telefone')
                            ->visible(fn (Get $get): bool => self::tipoSelecionado($get('tipo_contrato_id')) === 'Comodato')
                            ->columnSpan(6),
                        TextInput::make('dados.comodato_veiculo')
                            ->label('Veiculo')
                            ->visible(fn (Get $get): bool => self::tipoSelecionado($get('tipo_contrato_id')) === 'Comodato')
                            ->columnSpan(6),
                        TextInput::make('dados.comodato_placa')
                            ->label('Placa')
                            ->visible(fn (Get $get): bool => self::tipoSelecionado($get('tipo_contrato_id')) === 'Comodato')
                            ->columnSpan(6),
                        DatePicker::make('dados.comodato_data_instalacao')
                            ->label('Data instalacao')
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->visible(fn (Get $get): bool => self::tipoSelecionado($get('tipo_contrato_id')) === 'Comodato')
                            ->columnSpan(6),
                        TextInput::make('dados.comodato_tecnico')
                            ->label('Tecnico')
                            ->visible(fn (Get $get): bool => self::tipoSelecionado($get('tipo_contrato_id')) === 'Comodato')
                            ->columnSpan(6),
                        TextInput::make('dados.comodato_empresa')
                            ->label('Empresa')
                            ->visible(fn (Get $get): bool => self::tipoSelecionado($get('tipo_contrato_id')) === 'Comodato')
                            ->columnSpan(6),
                        TextInput::make('dados.comodato_cnpj')
                            ->label('CNPJ')
                            ->visible(fn (Get $get): bool => self::tipoSelecionado($get('tipo_contrato_id')) === 'Comodato')
                            ->columnSpan(6),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    /**
     * @return array<int, string>
     */
    private static function veiculoOptions(int $clienteId): array
    {
        if ($clienteId <= 0) {
            return [];
        }

        return Veiculo::query()
            ->with(['rastreador', 'statusRastreador'])
            ->where('cliente_id', $clienteId)
            ->whereNull('data_exclusao')
            ->orderByDesc('updated_at')
            ->get()
            ->mapWithKeys(function (Veiculo $veiculo): array {
                $imei = $veiculo->rastreador?->imei ?: 'Sem IMEI';
                $placa = $veiculo->placa ?: 'Sem placa';
                $status = $veiculo->statusRastreador?->label ?: 'Sem status';

                return [$veiculo->id => $imei.' - '.$veiculo->veiculo.' / '.$placa.' ('.$status.')'];
            })
            ->all();
    }

    private static function previewContrato(int $veiculoId, ?string $tipoContrato): string
    {
        if ($veiculoId <= 0 || blank($tipoContrato)) {
            return '';
        }

        $veiculo = Veiculo::query()
            ->with(['cliente.estado', 'tecnicoInstala'])
            ->find($veiculoId);

        if (! $veiculo) {
            return '';
        }

        $cliente = $veiculo->cliente;
        $telefone = self::formatTelefoneComPais($cliente?->telefone1_pais, $cliente?->telefone1);
        $endereco = collect([$cliente?->rua, $cliente?->numero, $cliente?->setor])->filter()->implode(' ');

        if ($tipoContrato === 'Comodato') {
            $items = [
                'Empresa' => $cliente?->nome,
                'CNPJ' => $cliente?->cpf_cnpj_formatado,
                'Veiculo' => $veiculo->veiculo,
                'Placa' => $veiculo->placa,
                'Data instalacao' => $veiculo->data_instalacao?->format('d/m/Y'),
                'Tecnico' => $veiculo->tecnicoInstala?->nome,
            ];
        } else {
            $items = [
                'Contratante' => $cliente?->nome,
                'CPF/CNPJ' => $cliente?->cpf_cnpj_formatado,
                'Contato' => $telefone,
                'Endereco' => $endereco,
                'Cep' => $cliente?->cep,
                'Cidade' => $cliente?->cidade,
                'UF' => $cliente?->estado?->label,
                'Email' => $cliente?->email,
                'Veiculo' => $veiculo->veiculo,
                'Placa' => $veiculo->placa,
                'Data de Instalacao' => $veiculo->data_instalacao?->format('d/m/Y'),
                'Valor de Instalacao' => number_format((float) ($veiculo->valor_instalacao ?? 0), 2, ',', '.'),
                'Data de Vencimento' => $cliente?->dia_pagamento,
            ];
        }

        $html = '<div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px 18px;padding:12px 14px;border:1px solid #e5e7eb;border-radius:8px;background:#f9fafb;color:#374151;font-size:13px;">';
        foreach ($items as $label => $value) {
            $html .= '<div style="min-width:0;overflow-wrap:anywhere;"><strong style="color:#111827;">'.e($label).':</strong> '.e((string) ($value ?? '')).'</div>';
        }
        $html .= '</div>';

        return $html;
    }

    private static function formatTelefone(mixed $value): string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        if (strlen($digits) === 11) {
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $digits) ?? $digits;
        }

        return (string) $value;
    }

    private static function formatTelefoneComPais(mixed $pais, mixed $telefone): string
    {
        $pais = Pais::codigoTelefone(Pais::normalizarCodigoTelefone((string) $pais) ?? (string) $pais);
        $telefoneFormatado = self::formatTelefone($telefone);

        return $telefoneFormatado === '' ? '' : '+'.$pais.' '.$telefoneFormatado;
    }

    private static function preencherDadosPadrao(Set $set, Get $get, mixed $veiculoId): void
    {
        if (self::tipoSelecionado($get('tipo_contrato_id')) !== 'Comodato' || blank($veiculoId)) {
            return;
        }

        $veiculo = Veiculo::query()
            ->with(['cliente', 'tecnicoInstala'])
            ->find($veiculoId);

        if (! $veiculo) {
            return;
        }

        $cliente = $veiculo->cliente;

        $set('dados.comodato_veiculo', $veiculo->veiculo);
        $set('dados.comodato_placa', $veiculo->placa);
        $set('dados.comodato_data_instalacao', $veiculo->data_instalacao?->format('Y-m-d'));
        $set('dados.comodato_tecnico', $veiculo->tecnicoInstala?->nome);
        $set('dados.comodato_empresa', $cliente?->nome);
        $set('dados.comodato_cnpj', $cliente?->cpf_cnpj_formatado);
        $set('dados.comodato_email', $cliente?->email);
        $set('dados.comodato_telefone', self::formatTelefoneComPais($cliente?->telefone1_pais, $cliente?->telefone1));
    }

    private static function tipoSelecionado(mixed $tipoContratoId): ?string
    {
        if (blank($tipoContratoId)) {
            return null;
        }

        return TipoContrato::query()->whereKey($tipoContratoId)->value('label');
    }
}
