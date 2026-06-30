<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('numr')->nullable();
            $table->foreignId('vendedor_id')->nullable()->constrained('vendedores')->nullOnDelete();
            $table->foreignId('status_contrato_id')->nullable()->constrained('status_contratos')->nullOnDelete();
            $table->foreignId('status_cliente_id')->nullable()->constrained('status_clientes')->nullOnDelete();
            $table->foreignId('cliente_origem_id')->nullable()->constrained('cliente_origens')->nullOnDelete();
            $table->foreignId('estado_id')->nullable()->constrained('estados')->nullOnDelete();
            $table->string('nome', 100);
            $table->string('cpf_cnpj', 50)->unique();
            $table->string('rg', 50)->nullable();
            $table->date('nascimento')->nullable();
            $table->string('email', 250)->nullable();
            $table->string('cep', 50)->nullable();
            $table->string('rua', 150)->nullable();
            $table->string('numero', 50)->nullable();
            $table->string('complemento', 50)->nullable();
            $table->string('setor', 50)->nullable();
            $table->string('cidade', 50)->nullable();
            $table->string('telefone1', 50);
            $table->string('telefone2', 50)->nullable();
            $table->string('empresa', 50)->nullable();
            $table->string('indicacao', 50)->nullable();
            $table->date('data_adesao');
            $table->dateTime('data_exclusao')->nullable();
            $table->unsignedTinyInteger('dia_pagamento');
            $table->boolean('is_spc')->default(false);
            $table->text('anotacoes')->nullable();
            $table->boolean('replicar_pagamento')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
