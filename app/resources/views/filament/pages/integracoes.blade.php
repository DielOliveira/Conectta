<x-filament-panels::page>
    <style>
        .ct-integrations {
            --ct-primary: #f59e0b;
            --ct-primary-strong: #d97706;
            --ct-primary-soft: rgba(245, 158, 11, 0.18);
            display: grid;
            gap: 18px;
            max-width: 1180px;
        }

        .ct-integration-card {
            background: #ffffff;
            border: 1px solid #d9dee7;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            padding: 24px;
        }

        .ct-integration-header {
            align-items: start;
            display: flex;
            gap: 16px;
            justify-content: space-between;
            margin-bottom: 22px;
        }

        .ct-integration-title {
            color: #0f172a;
            font-size: 22px;
            font-weight: 800;
            margin: 0;
        }

        .ct-integration-subtitle {
            color: #64748b;
            font-size: 14px;
            margin-top: 4px;
        }

        .ct-env-grid {
            display: grid;
            gap: 18px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .ct-env-card {
            background: #ffffff;
            border: 1px solid #d9dee7;
            border-radius: 8px;
            padding: 22px;
        }

        .ct-env-card.is-active {
            border-color: var(--ct-primary);
            box-shadow: 0 0 0 3px var(--ct-primary-soft);
        }

        .ct-status-pill {
            background: #f1f5f9;
            border-radius: 999px;
            color: #475569;
            font-size: 13px;
            font-weight: 700;
            padding: 8px 12px;
            white-space: nowrap;
        }

        .ct-status-pill.is-active {
            background: #dcfce7;
            color: #166534;
        }

        .ct-form-grid {
            display: grid;
            gap: 18px;
            grid-template-columns: repeat(12, minmax(0, 1fr));
        }

        .ct-field {
            display: grid;
            gap: 6px;
        }

        .ct-col-3 { grid-column: span 3; }
        .ct-col-4 { grid-column: span 4; }
        .ct-col-6 { grid-column: span 6; }
        .ct-col-8 { grid-column: span 8; }
        .ct-col-12 { grid-column: span 12; }

        .ct-label {
            color: #334155;
            font-size: 14px;
            font-weight: 600;
        }

        .ct-input,
        .ct-select {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            color: #0f172a;
            font-size: 15px;
            height: 44px;
            outline: none;
            padding: 0 12px;
            width: 100%;
        }

        .ct-input:focus,
        .ct-select:focus {
            border-color: var(--ct-primary);
            box-shadow: 0 0 0 3px var(--ct-primary-soft);
        }

        .ct-token-note {
            color: #64748b;
            font-size: 12px;
        }

        .ct-error {
            color: #dc2626;
            font-size: 12px;
        }

        .ct-actions {
            align-items: center;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 22px;
        }

        .ct-btn {
            align-items: center;
            background: #ffffff;
            border: 1px solid #d9dee7;
            border-radius: 8px;
            color: #0f172a;
            cursor: pointer;
            display: inline-flex;
            font-size: 14px;
            font-weight: 800;
            height: 44px;
            justify-content: center;
            padding: 0 18px;
        }

        .ct-btn-primary {
            background: var(--ct-primary);
            border-color: var(--ct-primary);
            color: #111827;
        }

        @media (max-width: 1000px) {
            .ct-env-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 760px) {
            .ct-col-3,
            .ct-col-4,
            .ct-col-6,
            .ct-col-8,
            .ct-col-12 {
                grid-column: span 12;
            }

            .ct-integration-header {
                display: grid;
            }
        }
    </style>

    <form class="ct-integrations" wire:submit.prevent="salvarLytex">
        <section class="ct-integration-card">
            <div class="ct-integration-header">
                <div>
                    <h2 class="ct-integration-title">Lytex</h2>
                    <div class="ct-integration-subtitle">
                        Configure os dados de Homologacao e Producao separadamente. O ambiente ativo e o usado para gerar, consultar e cancelar boletos.
                    </div>
                </div>
            </div>

            <div class="ct-form-grid">
                <label class="ct-field ct-col-4">
                    <span class="ct-label">Ambiente ativo</span>
                    <select class="ct-select" wire:model="lytexAmbienteAtivo">
                        <option value="homologacao">Homologacao</option>
                        <option value="producao">Producao</option>
                    </select>
                    @error('lytexAmbienteAtivo') <span class="ct-error">{{ $message }}</span> @enderror
                </label>
            </div>
        </section>

        <div class="ct-env-grid">
            <section class="ct-env-card {{ $lytexAmbienteAtivo === 'homologacao' ? 'is-active' : '' }}">
                <div class="ct-integration-header">
                    <div>
                        <h3 class="ct-integration-title">Homologacao</h3>
                        <div class="ct-integration-subtitle">Dados usados para testes com a Lytex.</div>
                    </div>

                    <div class="ct-status-pill {{ $lytexAmbienteAtivo === 'homologacao' ? 'is-active' : '' }}">
                        {{ $lytexAmbienteAtivo === 'homologacao' ? 'Ativo' : 'Inativo' }}
                    </div>
                </div>

                <div class="ct-form-grid">
                    <label class="ct-field ct-col-12">
                        <span class="ct-label">URL base</span>
                        <input class="ct-input" type="url" wire:model="lytexHomologacaoBaseUrl" placeholder="https://api-pay.lytex.com.br">
                        @error('lytexHomologacaoBaseUrl') <span class="ct-error">{{ $message }}</span> @enderror
                    </label>

                    <label class="ct-field ct-col-6">
                        <span class="ct-label">ClientId</span>
                        <input class="ct-input" type="text" wire:model="lytexHomologacaoClientId" autocomplete="off" placeholder="ClientId da Lytex">
                        @error('lytexHomologacaoClientId') <span class="ct-error">{{ $message }}</span> @enderror
                    </label>

                    <label class="ct-field ct-col-6">
                        <span class="ct-label">ClientSecret</span>
                        <input class="ct-input" type="password" wire:model="lytexHomologacaoClientSecret" autocomplete="new-password" placeholder="{{ $lytexHomologacaoClientSecretCadastrado ? 'ClientSecret cadastrado. Digite apenas para trocar.' : 'Informe o ClientSecret da Lytex' }}">
                        <span class="ct-token-note">
                            {{ $lytexHomologacaoClientSecretCadastrado ? 'O ClientSecret salvo fica criptografado e nao e exibido.' : 'O ClientSecret sera salvo criptografado.' }}
                        </span>
                        @error('lytexHomologacaoClientSecret') <span class="ct-error">{{ $message }}</span> @enderror
                    </label>

                    <label class="ct-field ct-col-12">
                        <span class="ct-label">Callback Secret do Webhook</span>
                        <input class="ct-input" type="password" wire:model="lytexHomologacaoCallbackSecret" autocomplete="new-password" placeholder="{{ $lytexHomologacaoCallbackSecretCadastrado ? 'Callback Secret cadastrado. Digite apenas para trocar.' : 'Informe o Callback Secret configurado na Lytex' }}">
                        <span class="ct-token-note">
                            {{ $lytexHomologacaoCallbackSecretCadastrado ? 'O Callback Secret salvo fica criptografado e nao e exibido.' : 'Usado para validar a assinatura dos webhooks da Lytex.' }}
                        </span>
                        @error('lytexHomologacaoCallbackSecret') <span class="ct-error">{{ $message }}</span> @enderror
                    </label>

                    <label class="ct-field ct-col-6">
                        <span class="ct-label">Autenticacao</span>
                        <input class="ct-input" type="text" wire:model="lytexHomologacaoAuthScheme" placeholder="Bearer">
                        @error('lytexHomologacaoAuthScheme') <span class="ct-error">{{ $message }}</span> @enderror
                    </label>

                    <label class="ct-field ct-col-6">
                        <span class="ct-label">Timeout</span>
                        <input class="ct-input" type="number" min="5" max="120" wire:model="lytexHomologacaoTimeout">
                        @error('lytexHomologacaoTimeout') <span class="ct-error">{{ $message }}</span> @enderror
                    </label>
                </div>
            </section>

            <section class="ct-env-card {{ $lytexAmbienteAtivo === 'producao' ? 'is-active' : '' }}">
                <div class="ct-integration-header">
                    <div>
                        <h3 class="ct-integration-title">Producao</h3>
                        <div class="ct-integration-subtitle">Dados usados para emissao real de boletos.</div>
                    </div>

                    <div class="ct-status-pill {{ $lytexAmbienteAtivo === 'producao' ? 'is-active' : '' }}">
                        {{ $lytexAmbienteAtivo === 'producao' ? 'Ativo' : 'Inativo' }}
                    </div>
                </div>

                <div class="ct-form-grid">
                    <label class="ct-field ct-col-12">
                        <span class="ct-label">URL base</span>
                        <input class="ct-input" type="url" wire:model="lytexProducaoBaseUrl" placeholder="https://api-pay.lytex.com.br">
                        @error('lytexProducaoBaseUrl') <span class="ct-error">{{ $message }}</span> @enderror
                    </label>

                    <label class="ct-field ct-col-6">
                        <span class="ct-label">ClientId</span>
                        <input class="ct-input" type="text" wire:model="lytexProducaoClientId" autocomplete="off" placeholder="ClientId da Lytex">
                        @error('lytexProducaoClientId') <span class="ct-error">{{ $message }}</span> @enderror
                    </label>

                    <label class="ct-field ct-col-6">
                        <span class="ct-label">ClientSecret</span>
                        <input class="ct-input" type="password" wire:model="lytexProducaoClientSecret" autocomplete="new-password" placeholder="{{ $lytexProducaoClientSecretCadastrado ? 'ClientSecret cadastrado. Digite apenas para trocar.' : 'Informe o ClientSecret da Lytex' }}">
                        <span class="ct-token-note">
                            {{ $lytexProducaoClientSecretCadastrado ? 'O ClientSecret salvo fica criptografado e nao e exibido.' : 'O ClientSecret sera salvo criptografado.' }}
                        </span>
                        @error('lytexProducaoClientSecret') <span class="ct-error">{{ $message }}</span> @enderror
                    </label>

                    <label class="ct-field ct-col-12">
                        <span class="ct-label">Callback Secret do Webhook</span>
                        <input class="ct-input" type="password" wire:model="lytexProducaoCallbackSecret" autocomplete="new-password" placeholder="{{ $lytexProducaoCallbackSecretCadastrado ? 'Callback Secret cadastrado. Digite apenas para trocar.' : 'Informe o Callback Secret configurado na Lytex' }}">
                        <span class="ct-token-note">
                            {{ $lytexProducaoCallbackSecretCadastrado ? 'O Callback Secret salvo fica criptografado e nao e exibido.' : 'Usado para validar a assinatura dos webhooks da Lytex.' }}
                        </span>
                        @error('lytexProducaoCallbackSecret') <span class="ct-error">{{ $message }}</span> @enderror
                    </label>

                    <label class="ct-field ct-col-6">
                        <span class="ct-label">Autenticacao</span>
                        <input class="ct-input" type="text" wire:model="lytexProducaoAuthScheme" placeholder="Bearer">
                        @error('lytexProducaoAuthScheme') <span class="ct-error">{{ $message }}</span> @enderror
                    </label>

                    <label class="ct-field ct-col-6">
                        <span class="ct-label">Timeout</span>
                        <input class="ct-input" type="number" min="5" max="120" wire:model="lytexProducaoTimeout">
                        @error('lytexProducaoTimeout') <span class="ct-error">{{ $message }}</span> @enderror
                    </label>
                </div>
            </section>
        </div>

        <div class="ct-actions">
            <button class="ct-btn ct-btn-primary" type="submit">Salvar alteracoes</button>
        </div>
    </form>

    <form class="ct-integrations" wire:submit.prevent="salvarZapSign">
        <section class="ct-integration-card">
            <div class="ct-integration-header">
                <div>
                    <h2 class="ct-integration-title">ZapSign</h2>
                    <div class="ct-integration-subtitle">Configure Homologacao e Producao separadamente. O ambiente ativo e usado para gerar contratos.</div>
                </div>
            </div>

            <div class="ct-form-grid">
                <label class="ct-field ct-col-4">
                    <span class="ct-label">Ambiente ativo</span>
                    <select class="ct-select" wire:model="zapsignAmbienteAtivo">
                        <option value="homologacao">Homologacao</option>
                        <option value="producao">Producao</option>
                    </select>
                    @error('zapsignAmbienteAtivo') <span class="ct-error">{{ $message }}</span> @enderror
                </label>
            </div>
        </section>

        <div class="ct-env-grid">
            @foreach ([['Homologacao', 'homologacao'], ['Producao', 'producao']] as [$label, $ambiente])
                @php
                    $prefix = $ambiente === 'producao' ? 'zapsignProducao' : 'zapsignHomologacao';
                    $isActive = $zapsignAmbienteAtivo === $ambiente;
                    $tokenFlag = $ambiente === 'producao' ? $zapsignProducaoTokenCadastrado : $zapsignHomologacaoTokenCadastrado;
                    $callbackSecretFlag = $ambiente === 'producao' ? $zapsignProducaoCallbackSecretCadastrado : $zapsignHomologacaoCallbackSecretCadastrado;
                @endphp

                <section class="ct-env-card {{ $isActive ? 'is-active' : '' }}">
                    <div class="ct-integration-header">
                        <div>
                            <h3 class="ct-integration-title">{{ $label }}</h3>
                            <div class="ct-integration-subtitle">Dados usados para {{ $ambiente === 'producao' ? 'envio real de contratos' : 'testes de contratos' }}.</div>
                        </div>
                        <div class="ct-status-pill {{ $isActive ? 'is-active' : '' }}">{{ $isActive ? 'Ativo' : 'Inativo' }}</div>
                    </div>

                    <div class="ct-form-grid">
                        <label class="ct-field ct-col-12">
                            <span class="ct-label">URL base</span>
                            <input class="ct-input" type="url" wire:model="{{ $prefix }}BaseUrl" placeholder="https://api.zapsign.com.br">
                            @error($prefix . 'BaseUrl') <span class="ct-error">{{ $message }}</span> @enderror
                        </label>
                        <label class="ct-field ct-col-8">
                            <span class="ct-label">Token</span>
                            <input class="ct-input" type="password" wire:model="{{ $prefix }}Token" autocomplete="new-password" placeholder="{{ $tokenFlag ? 'Token cadastrado. Digite apenas para trocar.' : 'Informe o token da ZapSign' }}">
                            <span class="ct-token-note">{{ $tokenFlag ? 'O token salvo fica criptografado e nao e exibido.' : 'O token sera salvo criptografado.' }}</span>
                            @error($prefix . 'Token') <span class="ct-error">{{ $message }}</span> @enderror
                        </label>

                        <label class="ct-field ct-col-12">
                            <span class="ct-label">Webhook Secret</span>
                            <input class="ct-input" type="password" wire:model="{{ $prefix }}CallbackSecret" autocomplete="new-password" placeholder="{{ $callbackSecretFlag ? 'Webhook Secret cadastrado. Digite apenas para trocar.' : 'Informe um segredo para validar os webhooks da ZapSign' }}">
                            <span class="ct-token-note">Configure este mesmo valor na ZapSign em um header, por exemplo: X-Conectta-Webhook-Token.</span>
                            @error($prefix . 'CallbackSecret') <span class="ct-error">{{ $message }}</span> @enderror
                        </label>
                        <label class="ct-field ct-col-4">
                            <span class="ct-label">Autenticacao</span>
                            <input class="ct-input" type="text" wire:model="{{ $prefix }}AuthScheme" placeholder="Bearer">
                            @error($prefix . 'AuthScheme') <span class="ct-error">{{ $message }}</span> @enderror
                        </label>
                        <label class="ct-field ct-col-4">
                            <span class="ct-label">Template Principal</span>
                            <input class="ct-input" type="text" wire:model="{{ $prefix }}TemplatePrincipalId">
                            @error($prefix . 'TemplatePrincipalId') <span class="ct-error">{{ $message }}</span> @enderror
                        </label>
                        <label class="ct-field ct-col-4">
                            <span class="ct-label">Template Aditivo</span>
                            <input class="ct-input" type="text" wire:model="{{ $prefix }}TemplateAditivoId">
                            @error($prefix . 'TemplateAditivoId') <span class="ct-error">{{ $message }}</span> @enderror
                        </label>
                        <label class="ct-field ct-col-4">
                            <span class="ct-label">Template Comodato</span>
                            <input class="ct-input" type="text" wire:model="{{ $prefix }}TemplateComodatoId">
                            @error($prefix . 'TemplateComodatoId') <span class="ct-error">{{ $message }}</span> @enderror
                        </label>
                        <label class="ct-field ct-col-4">
                            <span class="ct-label">Timeout</span>
                            <input class="ct-input" type="number" min="5" max="120" wire:model="{{ $prefix }}Timeout">
                            @error($prefix . 'Timeout') <span class="ct-error">{{ $message }}</span> @enderror
                        </label>
                    </div>
                </section>
            @endforeach
        </div>

        <div class="ct-actions">
            <button class="ct-btn ct-btn-primary" type="submit">Salvar ZapSign</button>
        </div>
    </form>

    <form class="ct-integrations" wire:submit.prevent="salvarZapi">
        <section class="ct-integration-card">
            <div class="ct-integration-header">
                <div>
                    <h2 class="ct-integration-title">Z-API WhatsApp</h2>
                    <div class="ct-integration-subtitle">Configure Homologacao e Producao separadamente. O ambiente ativo e usado para enviar mensagens de cobranca.</div>
                </div>
            </div>

            <div class="ct-form-grid">
                <label class="ct-field ct-col-4">
                    <span class="ct-label">Ambiente ativo</span>
                    <select class="ct-select" wire:model="zapiAmbienteAtivo">
                        <option value="homologacao">Homologacao</option>
                        <option value="producao">Producao</option>
                    </select>
                    @error('zapiAmbienteAtivo') <span class="ct-error">{{ $message }}</span> @enderror
                </label>
            </div>
        </section>

        <div class="ct-env-grid">
            @foreach ([['Homologacao', 'homologacao'], ['Producao', 'producao']] as [$label, $ambiente])
                @php
                    $prefix = $ambiente === 'producao' ? 'zapiProducao' : 'zapiHomologacao';
                    $isActive = $zapiAmbienteAtivo === $ambiente;
                    $tokenFlag = $ambiente === 'producao' ? $zapiProducaoTokenCadastrado : $zapiHomologacaoTokenCadastrado;
                    $clientTokenFlag = $ambiente === 'producao' ? $zapiProducaoClientTokenCadastrado : $zapiHomologacaoClientTokenCadastrado;
                @endphp

                <section class="ct-env-card {{ $isActive ? 'is-active' : '' }}">
                    <div class="ct-integration-header">
                        <div>
                            <h3 class="ct-integration-title">{{ $label }}</h3>
                            <div class="ct-integration-subtitle">Dados usados para {{ $ambiente === 'producao' ? 'envio real de WhatsApp' : 'testes de WhatsApp' }}.</div>
                        </div>
                        <div class="ct-status-pill {{ $isActive ? 'is-active' : '' }}">{{ $isActive ? 'Ativo' : 'Inativo' }}</div>
                    </div>

                    <div class="ct-form-grid">
                        <label class="ct-field ct-col-12">
                            <span class="ct-label">URL base</span>
                            <input class="ct-input" type="url" wire:model="{{ $prefix }}BaseUrl" placeholder="https://api.z-api.io">
                            @error($prefix . 'BaseUrl') <span class="ct-error">{{ $message }}</span> @enderror
                        </label>

                        <label class="ct-field ct-col-6">
                            <span class="ct-label">Instance ID</span>
                            <input class="ct-input" type="text" wire:model="{{ $prefix }}InstanceId" autocomplete="off" placeholder="ID da instancia Z-API">
                            @error($prefix . 'InstanceId') <span class="ct-error">{{ $message }}</span> @enderror
                        </label>

                        <label class="ct-field ct-col-6">
                            <span class="ct-label">Endpoint PIX</span>
                            <input class="ct-input" type="text" wire:model="{{ $prefix }}PixEndpoint" placeholder="send-button-pix">
                            @error($prefix . 'PixEndpoint') <span class="ct-error">{{ $message }}</span> @enderror
                        </label>

                        <label class="ct-field ct-col-6">
                            <span class="ct-label">Token</span>
                            <input class="ct-input" type="password" wire:model="{{ $prefix }}Token" autocomplete="new-password" placeholder="{{ $tokenFlag ? 'Token cadastrado. Digite apenas para trocar.' : 'Informe o token da instancia' }}">
                            <span class="ct-token-note">{{ $tokenFlag ? 'O token salvo fica criptografado e nao e exibido.' : 'O token sera salvo criptografado.' }}</span>
                            @error($prefix . 'Token') <span class="ct-error">{{ $message }}</span> @enderror
                        </label>

                        <label class="ct-field ct-col-6">
                            <span class="ct-label">Client Token</span>
                            <input class="ct-input" type="password" wire:model="{{ $prefix }}ClientToken" autocomplete="new-password" placeholder="{{ $clientTokenFlag ? 'Client Token cadastrado. Digite apenas para trocar.' : 'Informe o Client Token da Z-API' }}">
                            <span class="ct-token-note">{{ $clientTokenFlag ? 'O Client Token salvo fica criptografado e nao e exibido.' : 'O Client Token sera salvo criptografado.' }}</span>
                            @error($prefix . 'ClientToken') <span class="ct-error">{{ $message }}</span> @enderror
                        </label>

                        <label class="ct-field ct-col-4">
                            <span class="ct-label">Timeout</span>
                            <input class="ct-input" type="number" min="5" max="120" wire:model="{{ $prefix }}Timeout">
                            @error($prefix . 'Timeout') <span class="ct-error">{{ $message }}</span> @enderror
                        </label>
                    </div>
                </section>
            @endforeach
        </div>

        <div class="ct-actions">
            <button class="ct-btn ct-btn-primary" type="submit">Salvar Z-API</button>
        </div>
    </form>
</x-filament-panels::page>
