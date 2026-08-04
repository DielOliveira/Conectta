<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordem_servico_numeracoes', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('ordem_servico_disponibilidades', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tecnico_id')->constrained('tecnicos')->restrictOnDelete();
            $table->date('data');
            $table->time('hora_inicio');
            $table->time('hora_fim');
            $table->timestamps();
            $table->index(['tecnico_id', 'data']);
        });

        Schema::create('ordens_servico', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('numero')->unique();
            $table->string('tipo', 30);
            $table->string('status', 50)->default('aberta');
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('veiculo_id')->constrained('veiculos')->restrictOnDelete();
            $table->foreignId('tecnico_id')->nullable()->constrained('tecnicos')->restrictOnDelete();
            $table->foreignId('disponibilidade_id')->nullable()->constrained('ordem_servico_disponibilidades')->restrictOnDelete();
            $table->dateTime('atendimento_desejado_em');
            $table->dateTime('agendado_em')->nullable();
            $table->string('endereco', 500);
            $table->text('descricao');
            $table->text('observacoes')->nullable();
            $table->text('localizacao_url')->nullable();
            $table->decimal('localizacao_latitude', 10, 7)->nullable();
            $table->decimal('localizacao_longitude', 10, 7)->nullable();
            $table->boolean('notificar_cliente')->default(false);
            $table->string('token_hash', 64)->nullable()->unique();
            $table->text('token_credencial')->nullable();
            $table->dateTime('token_invalidado_em')->nullable();
            $table->dateTime('aceita_em')->nullable();
            $table->dateTime('iniciada_em')->nullable();
            $table->decimal('inicio_latitude', 10, 7)->nullable();
            $table->decimal('inicio_longitude', 10, 7)->nullable();
            $table->dateTime('termino_tecnico_em')->nullable();
            $table->dateTime('finalizada_em')->nullable();
            $table->foreignId('finalizada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cancelada_em')->nullable();
            $table->foreignId('cancelada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->text('motivo_cancelamento')->nullable();
            $table->text('motivo_pendencia')->nullable();
            $table->string('resultado_manutencao', 50)->nullable();
            $table->text('descricao_atendimento')->nullable();
            $table->boolean('equipamentos_confirmados')->default(false);
            $table->foreignId('rastreador_anterior_id')->nullable()->constrained('rastreadores')->restrictOnDelete();
            $table->foreignId('chip_anterior_id')->nullable()->constrained('chips')->restrictOnDelete();
            $table->foreignId('rastreador_novo_id')->nullable()->constrained('rastreadores')->restrictOnDelete();
            $table->foreignId('chip_novo_id')->nullable()->constrained('chips')->restrictOnDelete();
            $table->boolean('check_funcionamento')->nullable();
            $table->boolean('check_pos_chave')->nullable();
            $table->string('check_bloqueio', 20)->nullable();
            $table->timestamps();
            $table->index(['status', 'agendado_em']);
            $table->index(['veiculo_id', 'status']);
        });

        Schema::create('ordem_servico_historicos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ordem_servico_id')->constrained('ordens_servico')->cascadeOnDelete();
            $table->string('evento', 80);
            $table->string('status_anterior', 50)->nullable();
            $table->string('status_novo', 50)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('tecnico_id')->nullable()->constrained('tecnicos')->nullOnDelete();
            $table->text('observacao')->nullable();
            $table->json('contexto')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['ordem_servico_id', 'created_at']);
        });

        Schema::create('ordem_servico_fotos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ordem_servico_id')->constrained('ordens_servico')->cascadeOnDelete();
            $table->string('caminho', 500);
            $table->string('nome_original', 255)->nullable();
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('tamanho');
            $table->timestamps();
        });

        Schema::create('ordem_servico_notificacoes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ordem_servico_id')->constrained('ordens_servico')->cascadeOnDelete();
            $table->string('destinatario_tipo', 20);
            $table->string('evento', 50);
            $table->string('telefone', 30)->nullable();
            $table->text('mensagem');
            $table->string('status', 30)->default('pendente');
            $table->unsignedSmallInteger('tentativas')->default(0);
            $table->text('erro')->nullable();
            $table->dateTime('enviada_em')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        foreach ([
            'OS_Escrita' => ['Ordens de Serviço - Escrita', 'Escrita', 140],
            'OS_Leitura' => ['Ordens de Serviço - Leitura', 'Leitura', 150],
        ] as $nome => [$label, $acao, $ordem]) {
            DB::table('permissions')->updateOrInsert(['nome' => $nome], [
                'label' => $label, 'modulo' => 'Ordens de Serviço', 'acao' => $acao,
                'ordem' => $ordem, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('nome', ['OS_Escrita', 'OS_Leitura'])->delete();
        Schema::dropIfExists('ordem_servico_notificacoes');
        Schema::dropIfExists('ordem_servico_fotos');
        Schema::dropIfExists('ordem_servico_historicos');
        Schema::dropIfExists('ordens_servico');
        Schema::dropIfExists('ordem_servico_disponibilidades');
        Schema::dropIfExists('ordem_servico_numeracoes');
    }
};
