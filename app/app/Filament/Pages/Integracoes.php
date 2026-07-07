<?php

namespace App\Filament\Pages;

use App\Models\ConfiguracaoIntegracao;
use App\Models\Permission;
use App\Models\TokenLytex;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Integracoes extends Page
{
    protected static ?string $slug = 'integracoes';

    protected static string|UnitEnum|null $navigationGroup = 'Administrativo';

    protected static ?int $navigationSort = 1;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $navigationLabel = 'Integracoes';

    protected static ?string $title = 'Integracoes';

    protected string $view = 'filament.pages.integracoes';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission(Permission::TECNICO) ?? false;
    }

    public string $lytexAmbienteAtivo = 'producao';

    public string $lytexProducaoBaseUrl = '';

    public string $lytexProducaoClientId = '';

    public string $lytexProducaoClientSecret = '';

    public string $lytexProducaoCallbackSecret = '';

    public string $lytexProducaoAuthScheme = 'Bearer';

    public int $lytexProducaoTimeout = 30;

    public bool $lytexProducaoClientSecretCadastrado = false;

    public bool $lytexProducaoCallbackSecretCadastrado = false;

    public string $lytexHomologacaoBaseUrl = '';

    public string $lytexHomologacaoClientId = '';

    public string $lytexHomologacaoClientSecret = '';

    public string $lytexHomologacaoCallbackSecret = '';

    public string $lytexHomologacaoAuthScheme = 'Bearer';

    public int $lytexHomologacaoTimeout = 30;

    public bool $lytexHomologacaoClientSecretCadastrado = false;

    public bool $lytexHomologacaoCallbackSecretCadastrado = false;

    public string $zapsignAmbienteAtivo = 'producao';
    public string $zapsignProducaoBaseUrl = '';
    public string $zapsignProducaoToken = '';
    public string $zapsignProducaoCallbackSecret = '';
    public string $zapsignProducaoAuthScheme = 'Bearer';
    public int $zapsignProducaoTimeout = 30;
    public string $zapsignProducaoTemplatePrincipalId = '';
    public string $zapsignProducaoTemplateAditivoId = '';
    public string $zapsignProducaoTemplateComodatoId = '';
    public bool $zapsignProducaoTokenCadastrado = false;
    public bool $zapsignProducaoCallbackSecretCadastrado = false;
    public string $zapsignHomologacaoBaseUrl = '';
    public string $zapsignHomologacaoToken = '';
    public string $zapsignHomologacaoCallbackSecret = '';
    public string $zapsignHomologacaoAuthScheme = 'Bearer';
    public int $zapsignHomologacaoTimeout = 30;
    public string $zapsignHomologacaoTemplatePrincipalId = '';
    public string $zapsignHomologacaoTemplateAditivoId = '';
    public string $zapsignHomologacaoTemplateComodatoId = '';
    public bool $zapsignHomologacaoTokenCadastrado = false;
    public bool $zapsignHomologacaoCallbackSecretCadastrado = false;

    public string $zapiAmbienteAtivo = 'producao';
    public string $zapiProducaoBaseUrl = '';
    public string $zapiProducaoInstanceId = '';
    public string $zapiProducaoToken = '';
    public string $zapiProducaoClientToken = '';
    public int $zapiProducaoTimeout = 30;
    public string $zapiProducaoPixEndpoint = 'send-button-pix';
    public bool $zapiProducaoTokenCadastrado = false;
    public bool $zapiProducaoClientTokenCadastrado = false;
    public string $zapiHomologacaoBaseUrl = '';
    public string $zapiHomologacaoInstanceId = '';
    public string $zapiHomologacaoToken = '';
    public string $zapiHomologacaoClientToken = '';
    public int $zapiHomologacaoTimeout = 30;
    public string $zapiHomologacaoPixEndpoint = 'send-button-pix';
    public bool $zapiHomologacaoTokenCadastrado = false;
    public bool $zapiHomologacaoClientTokenCadastrado = false;

    public function mount(): void
    {
        $producao = ConfiguracaoIntegracao::lytexAmbiente('producao');
        $homologacao = ConfiguracaoIntegracao::lytexAmbiente('homologacao');
        $ativa = ConfiguracaoIntegracao::lytexAtiva();

        $zapsignProducao = ConfiguracaoIntegracao::zapsignAmbiente('producao');
        $zapsignHomologacao = ConfiguracaoIntegracao::zapsignAmbiente('homologacao');
        $zapsignAtiva = ConfiguracaoIntegracao::zapsignAtiva();

        $zapiProducao = ConfiguracaoIntegracao::zapiAmbiente('producao');
        $zapiHomologacao = ConfiguracaoIntegracao::zapiAmbiente('homologacao');
        $zapiAtiva = ConfiguracaoIntegracao::zapiAtiva();

        $this->lytexAmbienteAtivo = (string) $ativa->ambiente;
        $this->carregarAmbiente('producao', $producao);
        $this->carregarAmbiente('homologacao', $homologacao);

        $this->zapsignAmbienteAtivo = (string) $zapsignAtiva->ambiente;
        $this->carregarZapSignAmbiente('producao', $zapsignProducao);
        $this->carregarZapSignAmbiente('homologacao', $zapsignHomologacao);

        $this->zapiAmbienteAtivo = (string) $zapiAtiva->ambiente;
        $this->carregarZapiAmbiente('producao', $zapiProducao);
        $this->carregarZapiAmbiente('homologacao', $zapiHomologacao);
    }

    public function salvarLytex(): void
    {
        if (! auth()->user()?->isAdmin()) {
            Notification::make()->title('Voce nao tem permissao para esta acao.')->danger()->send();

            return;
        }

        $data = $this->validate(
            [
                'lytexAmbienteAtivo' => ['required', 'in:producao,homologacao'],
                'lytexProducaoBaseUrl' => ['required', 'url', 'max:255'],
                'lytexProducaoClientId' => ['nullable', 'string', 'max:255'],
                'lytexProducaoClientSecret' => ['nullable', 'string', 'max:5000'],
                'lytexProducaoCallbackSecret' => ['nullable', 'string', 'max:5000'],
                'lytexProducaoAuthScheme' => ['nullable', 'string', 'max:30'],
                'lytexProducaoTimeout' => ['required', 'integer', 'min:5', 'max:120'],
                'lytexHomologacaoBaseUrl' => ['required', 'url', 'max:255'],
                'lytexHomologacaoClientId' => ['nullable', 'string', 'max:255'],
                'lytexHomologacaoClientSecret' => ['nullable', 'string', 'max:5000'],
                'lytexHomologacaoCallbackSecret' => ['nullable', 'string', 'max:5000'],
                'lytexHomologacaoAuthScheme' => ['nullable', 'string', 'max:30'],
                'lytexHomologacaoTimeout' => ['required', 'integer', 'min:5', 'max:120'],
            ],
            [
                'required' => 'O campo :attribute e obrigatorio.',
                'url' => 'O campo :attribute deve ser uma URL valida.',
                'integer' => 'O campo :attribute deve ser um numero inteiro.',
                'min' => 'O campo :attribute deve ser pelo menos :min.',
                'max' => 'O campo :attribute deve ter no maximo :max caracteres.',
                'in' => 'O campo :attribute e invalido.',
                'lytexProducaoTimeout.max' => 'O campo :attribute deve ser no maximo :max.',
                'lytexHomologacaoTimeout.max' => 'O campo :attribute deve ser no maximo :max.',
            ],
            [
                'lytexAmbienteAtivo' => 'ambiente ativo',
                'lytexProducaoBaseUrl' => 'URL base de producao',
                'lytexProducaoClientId' => 'ClientId de producao',
                'lytexProducaoClientSecret' => 'ClientSecret de producao',
                'lytexProducaoCallbackSecret' => 'Callback Secret de producao',
                'lytexProducaoAuthScheme' => 'autenticacao de producao',
                'lytexProducaoTimeout' => 'timeout de producao',
                'lytexHomologacaoBaseUrl' => 'URL base de homologacao',
                'lytexHomologacaoClientId' => 'ClientId de homologacao',
                'lytexHomologacaoClientSecret' => 'ClientSecret de homologacao',
                'lytexHomologacaoCallbackSecret' => 'Callback Secret de homologacao',
                'lytexHomologacaoAuthScheme' => 'autenticacao de homologacao',
                'lytexHomologacaoTimeout' => 'timeout de homologacao',
            ],
        );

        $ativaAnterior = ConfiguracaoIntegracao::lytexAtiva();
        $producaoAnterior = ConfiguracaoIntegracao::lytexAmbiente('producao');
        $homologacaoAnterior = ConfiguracaoIntegracao::lytexAmbiente('homologacao');

        $mudouAmbienteAtivo = $ativaAnterior->ambiente !== $data['lytexAmbienteAtivo'];
        $mudouProducao = $this->ambienteMudou($producaoAnterior, $data, 'Producao');
        $mudouHomologacao = $this->ambienteMudou($homologacaoAnterior, $data, 'Homologacao');

        $producao = $this->salvarAmbiente('producao', $data, 'Producao');
        $homologacao = $this->salvarAmbiente('homologacao', $data, 'Homologacao');

        ConfiguracaoIntegracao::query()
            ->where('integracao', 'lytex')
            ->update(['ativo' => false]);

        ConfiguracaoIntegracao::query()
            ->whereKey($data['lytexAmbienteAtivo'] === 'producao' ? $producao->id : $homologacao->id)
            ->update(['ativo' => true]);

        $this->lytexProducaoClientSecret = '';
        $this->lytexHomologacaoClientSecret = '';
        $this->lytexProducaoCallbackSecret = '';
        $this->lytexHomologacaoCallbackSecret = '';
        $this->lytexProducaoClientSecretCadastrado = filled($producao->client_secret);
        $this->lytexHomologacaoClientSecretCadastrado = filled($homologacao->client_secret);
        $this->lytexProducaoCallbackSecretCadastrado = filled($producao->callback_secret);
        $this->lytexHomologacaoCallbackSecretCadastrado = filled($homologacao->callback_secret);

        if ($mudouAmbienteAtivo || ($data['lytexAmbienteAtivo'] === 'producao' && $mudouProducao) || ($data['lytexAmbienteAtivo'] === 'homologacao' && $mudouHomologacao)) {
            TokenLytex::query()->delete();
        }

        Notification::make()
            ->title('Configuracao da Lytex salva.')
            ->success()
            ->send();
    }

    private function carregarAmbiente(string $ambiente, ConfiguracaoIntegracao $configuracao): void
    {
        $prefixo = $ambiente === 'producao' ? 'lytexProducao' : 'lytexHomologacao';

        $this->{$prefixo . 'BaseUrl'} = (string) $configuracao->base_url;
        $this->{$prefixo . 'ClientId'} = (string) $configuracao->client_id;
        $this->{$prefixo . 'AuthScheme'} = (string) ($configuracao->auth_scheme ?: 'Bearer');
        $this->{$prefixo . 'Timeout'} = (int) ($configuracao->timeout ?: 30);
        $this->{$prefixo . 'ClientSecretCadastrado'} = filled($configuracao->client_secret);
        $this->{$prefixo . 'CallbackSecretCadastrado'} = filled($configuracao->callback_secret);
    }

    private function salvarAmbiente(string $ambiente, array $data, string $sufixo): ConfiguracaoIntegracao
    {
        $dados = [
            'base_url' => rtrim($data["lytex{$sufixo}BaseUrl"], '/'),
            'client_id' => blank($data["lytex{$sufixo}ClientId"]) ? null : trim($data["lytex{$sufixo}ClientId"]),
            'auth_scheme' => blank($data["lytex{$sufixo}AuthScheme"]) ? null : trim($data["lytex{$sufixo}AuthScheme"]),
            'timeout' => (int) $data["lytex{$sufixo}Timeout"],
        ];

        if (filled($data["lytex{$sufixo}ClientSecret"])) {
            $dados['client_secret'] = trim($data["lytex{$sufixo}ClientSecret"]);
        }

        if (filled($data["lytex{$sufixo}CallbackSecret"])) {
            $dados['callback_secret'] = trim($data["lytex{$sufixo}CallbackSecret"]);
        }

        return ConfiguracaoIntegracao::query()->updateOrCreate(
            [
                'integracao' => 'lytex',
                'ambiente' => $ambiente,
            ],
            $dados,
        );
    }

    private function ambienteMudou(ConfiguracaoIntegracao $configuracao, array $data, string $sufixo): bool
    {
        return filled($data["lytex{$sufixo}ClientSecret"])
            || filled($data["lytex{$sufixo}CallbackSecret"])
            || $configuracao->base_url !== rtrim($data["lytex{$sufixo}BaseUrl"], '/')
            || $configuracao->client_id !== trim((string) $data["lytex{$sufixo}ClientId"]);
    }
    public function salvarZapSign(): void
    {
        if (! auth()->user()?->isAdmin()) {
            Notification::make()->title('Voce nao tem permissao para esta acao.')->danger()->send();

            return;
        }

        $data = $this->validate(
            [
                'zapsignAmbienteAtivo' => ['required', 'in:producao,homologacao'],
                'zapsignProducaoBaseUrl' => ['required', 'url', 'max:255'],
                'zapsignProducaoToken' => ['nullable', 'string', 'max:5000'],
                'zapsignProducaoAuthScheme' => ['nullable', 'string', 'max:30'],
                'zapsignProducaoCallbackSecret' => ['nullable', 'string', 'max:5000'],
                'zapsignProducaoTimeout' => ['required', 'integer', 'min:5', 'max:120'],
                'zapsignProducaoTemplatePrincipalId' => ['nullable', 'string', 'max:255'],
                'zapsignProducaoTemplateAditivoId' => ['nullable', 'string', 'max:255'],
                'zapsignProducaoTemplateComodatoId' => ['nullable', 'string', 'max:255'],
                'zapsignHomologacaoBaseUrl' => ['required', 'url', 'max:255'],
                'zapsignHomologacaoToken' => ['nullable', 'string', 'max:5000'],
                'zapsignHomologacaoAuthScheme' => ['nullable', 'string', 'max:30'],
                'zapsignHomologacaoCallbackSecret' => ['nullable', 'string', 'max:5000'],
                'zapsignHomologacaoTimeout' => ['required', 'integer', 'min:5', 'max:120'],
                'zapsignHomologacaoTemplatePrincipalId' => ['nullable', 'string', 'max:255'],
                'zapsignHomologacaoTemplateAditivoId' => ['nullable', 'string', 'max:255'],
                'zapsignHomologacaoTemplateComodatoId' => ['nullable', 'string', 'max:255'],
            ],
            [
                'required' => 'O campo :attribute e obrigatorio.',
                'url' => 'O campo :attribute deve ser uma URL valida.',
                'integer' => 'O campo :attribute deve ser um numero inteiro.',
                'min' => 'O campo :attribute deve ser pelo menos :min.',
                'max' => 'O campo :attribute deve ter no maximo :max caracteres.',
                'in' => 'O campo :attribute e invalido.',
            ],
            [
                'zapsignAmbienteAtivo' => 'ambiente ativo da ZapSign',
                'zapsignProducaoBaseUrl' => 'URL base de producao da ZapSign',
                'zapsignHomologacaoBaseUrl' => 'URL base de homologacao da ZapSign',
            ],
        );

        $producao = $this->salvarZapSignAmbiente('producao', $data, 'Producao');
        $homologacao = $this->salvarZapSignAmbiente('homologacao', $data, 'Homologacao');

        ConfiguracaoIntegracao::query()
            ->where('integracao', 'zapsign')
            ->update(['ativo' => false]);

        ConfiguracaoIntegracao::query()
            ->whereKey($data['zapsignAmbienteAtivo'] === 'producao' ? $producao->id : $homologacao->id)
            ->update(['ativo' => true]);

        $this->zapsignProducaoToken = '';
        $this->zapsignHomologacaoToken = '';
        $this->zapsignProducaoCallbackSecret = '';
        $this->zapsignHomologacaoCallbackSecret = '';
        $this->zapsignProducaoTokenCadastrado = filled($producao->token);
        $this->zapsignHomologacaoTokenCadastrado = filled($homologacao->token);
        $this->zapsignProducaoCallbackSecretCadastrado = filled($producao->callback_secret);
        $this->zapsignHomologacaoCallbackSecretCadastrado = filled($homologacao->callback_secret);

        Notification::make()->title('Configuracao da ZapSign salva.')->success()->send();
    }

    private function carregarZapSignAmbiente(string $ambiente, ConfiguracaoIntegracao $configuracao): void
    {
        $prefixo = $ambiente === 'producao' ? 'zapsignProducao' : 'zapsignHomologacao';

        $this->{$prefixo . 'BaseUrl'} = (string) $configuracao->base_url;
        $this->{$prefixo . 'AuthScheme'} = (string) ($configuracao->auth_scheme ?: 'Bearer');
        $this->{$prefixo . 'Timeout'} = (int) ($configuracao->timeout ?: 30);
        $this->{$prefixo . 'TemplatePrincipalId'} = (string) $configuracao->template_principal_id;
        $this->{$prefixo . 'TemplateAditivoId'} = (string) $configuracao->template_aditivo_id;
        $this->{$prefixo . 'TemplateComodatoId'} = (string) $configuracao->template_comodato_id;
        $this->{$prefixo . 'TokenCadastrado'} = filled($configuracao->token);
        $this->{$prefixo . 'CallbackSecretCadastrado'} = filled($configuracao->callback_secret);
    }

    private function salvarZapSignAmbiente(string $ambiente, array $data, string $sufixo): ConfiguracaoIntegracao
    {
        $dados = [
            'base_url' => rtrim($data["zapsign{$sufixo}BaseUrl"], '/'),
            'auth_scheme' => blank($data["zapsign{$sufixo}AuthScheme"]) ? null : trim($data["zapsign{$sufixo}AuthScheme"]),
            'timeout' => (int) $data["zapsign{$sufixo}Timeout"],
            'template_principal_id' => blank($data["zapsign{$sufixo}TemplatePrincipalId"]) ? null : trim($data["zapsign{$sufixo}TemplatePrincipalId"]),
            'template_aditivo_id' => blank($data["zapsign{$sufixo}TemplateAditivoId"]) ? null : trim($data["zapsign{$sufixo}TemplateAditivoId"]),
            'template_comodato_id' => blank($data["zapsign{$sufixo}TemplateComodatoId"]) ? null : trim($data["zapsign{$sufixo}TemplateComodatoId"]),
        ];

        if (filled($data["zapsign{$sufixo}Token"])) {
            $dados['token'] = trim($data["zapsign{$sufixo}Token"]);
        }

        if (filled($data["zapsign{$sufixo}CallbackSecret"])) {
            $dados['callback_secret'] = trim($data["zapsign{$sufixo}CallbackSecret"]);
        }

        return ConfiguracaoIntegracao::query()->updateOrCreate(
            ['integracao' => 'zapsign', 'ambiente' => $ambiente],
            $dados,
        );
    }

    public function salvarZapi(): void
    {
        if (! auth()->user()?->isAdmin()) {
            Notification::make()->title('Voce nao tem permissao para esta acao.')->danger()->send();

            return;
        }

        $data = $this->validate(
            [
                'zapiAmbienteAtivo' => ['required', 'in:producao,homologacao'],
                'zapiProducaoBaseUrl' => ['required', 'url', 'max:255'],
                'zapiProducaoInstanceId' => ['nullable', 'string', 'max:255'],
                'zapiProducaoToken' => ['nullable', 'string', 'max:5000'],
                'zapiProducaoClientToken' => ['nullable', 'string', 'max:5000'],
                'zapiProducaoTimeout' => ['required', 'integer', 'min:5', 'max:120'],
                'zapiProducaoPixEndpoint' => ['required', 'string', 'max:120'],
                'zapiHomologacaoBaseUrl' => ['required', 'url', 'max:255'],
                'zapiHomologacaoInstanceId' => ['nullable', 'string', 'max:255'],
                'zapiHomologacaoToken' => ['nullable', 'string', 'max:5000'],
                'zapiHomologacaoClientToken' => ['nullable', 'string', 'max:5000'],
                'zapiHomologacaoTimeout' => ['required', 'integer', 'min:5', 'max:120'],
                'zapiHomologacaoPixEndpoint' => ['required', 'string', 'max:120'],
            ],
            [
                'required' => 'O campo :attribute e obrigatorio.',
                'url' => 'O campo :attribute deve ser uma URL valida.',
                'integer' => 'O campo :attribute deve ser um numero inteiro.',
                'min' => 'O campo :attribute deve ser pelo menos :min.',
                'max' => 'O campo :attribute deve ter no maximo :max caracteres.',
                'in' => 'O campo :attribute e invalido.',
            ],
            [
                'zapiAmbienteAtivo' => 'ambiente ativo da Z-API',
                'zapiProducaoBaseUrl' => 'URL base de producao da Z-API',
                'zapiHomologacaoBaseUrl' => 'URL base de homologacao da Z-API',
            ],
        );

        $producao = $this->salvarZapiAmbiente('producao', $data, 'Producao');
        $homologacao = $this->salvarZapiAmbiente('homologacao', $data, 'Homologacao');

        ConfiguracaoIntegracao::query()
            ->where('integracao', 'zapi')
            ->update(['ativo' => false]);

        ConfiguracaoIntegracao::query()
            ->whereKey($data['zapiAmbienteAtivo'] === 'producao' ? $producao->id : $homologacao->id)
            ->update(['ativo' => true]);

        $this->zapiProducaoToken = '';
        $this->zapiHomologacaoToken = '';
        $this->zapiProducaoClientToken = '';
        $this->zapiHomologacaoClientToken = '';
        $this->zapiProducaoTokenCadastrado = filled($producao->token);
        $this->zapiHomologacaoTokenCadastrado = filled($homologacao->token);
        $this->zapiProducaoClientTokenCadastrado = filled($producao->client_secret);
        $this->zapiHomologacaoClientTokenCadastrado = filled($homologacao->client_secret);

        Notification::make()->title('Configuracao da Z-API salva.')->success()->send();
    }

    private function carregarZapiAmbiente(string $ambiente, ConfiguracaoIntegracao $configuracao): void
    {
        $prefixo = $ambiente === 'producao' ? 'zapiProducao' : 'zapiHomologacao';

        $this->{$prefixo . 'BaseUrl'} = (string) $configuracao->base_url;
        $this->{$prefixo . 'InstanceId'} = (string) $configuracao->client_id;
        $this->{$prefixo . 'Timeout'} = (int) ($configuracao->timeout ?: 30);
        $this->{$prefixo . 'PixEndpoint'} = (string) ($configuracao->pix_endpoint ?: 'send-button-pix');
        $this->{$prefixo . 'TokenCadastrado'} = filled($configuracao->token);
        $this->{$prefixo . 'ClientTokenCadastrado'} = filled($configuracao->client_secret);
    }

    private function salvarZapiAmbiente(string $ambiente, array $data, string $sufixo): ConfiguracaoIntegracao
    {
        $dados = [
            'base_url' => rtrim($data["zapi{$sufixo}BaseUrl"], '/'),
            'client_id' => blank($data["zapi{$sufixo}InstanceId"]) ? null : trim($data["zapi{$sufixo}InstanceId"]),
            'timeout' => (int) $data["zapi{$sufixo}Timeout"],
            'pix_endpoint' => trim($data["zapi{$sufixo}PixEndpoint"]),
        ];

        if (filled($data["zapi{$sufixo}Token"])) {
            $dados['token'] = trim($data["zapi{$sufixo}Token"]);
        }

        if (filled($data["zapi{$sufixo}ClientToken"])) {
            $dados['client_secret'] = trim($data["zapi{$sufixo}ClientToken"]);
        }

        return ConfiguracaoIntegracao::query()->updateOrCreate(
            ['integracao' => 'zapi', 'ambiente' => $ambiente],
            $dados,
        );
    }
}
