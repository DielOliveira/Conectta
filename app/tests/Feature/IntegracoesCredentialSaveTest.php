<?php

namespace Tests\Feature;

use App\Filament\Pages\Integracoes;
use App\Models\ConfiguracaoIntegracao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

class IntegracoesCredentialSaveTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_encrypted_credential_can_be_replaced(): void
    {
        $id = DB::table('configuracoes_integracao')->insertGetId([
            'integracao' => 'lytex',
            'ambiente' => 'producao',
            'base_url' => 'https://api-pay.lytex.com.br',
            'client_secret' => 'valor-criptografado-com-outra-chave',
            'timeout' => 30,
            'ativo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $metodo = new ReflectionMethod(Integracoes::class, 'atualizarOuCriarConfiguracao');
        $configuracao = $metodo->invoke(
            app(Integracoes::class),
            ['integracao' => 'lytex', 'ambiente' => 'producao'],
            ['client_secret' => 'novo-segredo', 'timeout' => 60],
        );

        $this->assertSame($id, $configuracao->id);
        $this->assertSame('novo-segredo', $configuracao->client_secret);
        $this->assertSame(60, $configuracao->timeout);
    }
}
