<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cobranca_execucoes', function (Blueprint $table): void {
            $table->id();
            $table->date('data_processamento');
            $table->string('status', 30)->default('processando');
            $table->boolean('dry_run')->default(false);
            $table->unsignedInteger('total_processados')->default(0);
            $table->unsignedInteger('total_enviados')->default(0);
            $table->unsignedInteger('total_ignorados')->default(0);
            $table->unsignedInteger('total_erros')->default(0);
            $table->timestamp('iniciado_em')->nullable();
            $table->timestamp('finalizado_em')->nullable();
            $table->text('mensagem')->nullable();
            $table->timestamps();

            $table->index(['data_processamento', 'status']);
        });

        Schema::create('cobranca_envios', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cobranca_execucao_id')->nullable()->constrained('cobranca_execucoes')->nullOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('lancamento_id')->constrained('lancamentos')->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('tipo', 50);
            $table->string('status', 30);
            $table->date('data_referencia');
            $table->date('vencimento')->nullable();
            $table->decimal('valor', 12, 2)->nullable();
            $table->unsignedInteger('tentativas')->default(0);
            $table->timestamp('processado_em')->nullable();
            $table->timestamp('enviado_em')->nullable();
            $table->string('telefone', 30)->nullable();
            $table->string('link_invoice', 1000)->nullable();
            $table->string('link_boleto', 1000)->nullable();
            $table->text('mensagem')->nullable();
            $table->text('erro')->nullable();
            $table->timestamps();

            $table->unique(['lancamento_id', 'tipo', 'data_referencia'], 'cobranca_envios_lanc_tipo_data_unique');
            $table->index(['tipo', 'status']);
            $table->index('data_referencia');
        });

        Schema::create('cobranca_mensagem_modelos', function (Blueprint $table): void {
            $table->id();
            $table->string('tipo', 50);
            $table->string('nome', 120);
            $table->string('canal', 30)->default('whatsapp');
            $table->unsignedSmallInteger('ordem')->default(10);
            $table->boolean('ativo')->default(true);
            $table->text('conteudo');
            $table->timestamps();

            $table->unique(['tipo', 'canal', 'ordem'], 'cobranca_msg_tipo_canal_ordem_unique');
            $table->index(['tipo', 'ativo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cobranca_mensagem_modelos');
        Schema::dropIfExists('cobranca_envios');
        Schema::dropIfExists('cobranca_execucoes');
    }
};
