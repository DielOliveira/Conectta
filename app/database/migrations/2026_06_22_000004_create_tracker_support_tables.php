<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_veiculos', function (Blueprint $table): void {
            $table->id();
            $table->string('label', 50);
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tecnicos', function (Blueprint $table): void {
            $table->id();
            $table->string('nome', 50);
            $table->string('cpf', 50)->nullable();
            $table->string('telefone', 50)->nullable();
            $table->boolean('is_ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('chips', function (Blueprint $table): void {
            $table->id();
            $table->string('fornecedor', 50)->nullable();
            $table->string('operadora', 50)->nullable();
            $table->string('iccid', 50);
            $table->foreignId('tecnico_id')->nullable()->constrained('tecnicos')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('rastreadores', function (Blueprint $table): void {
            $table->id();
            $table->string('modelo', 50)->nullable();
            $table->integer('ativacao')->nullable();
            $table->string('imei', 50);
            $table->foreignId('tecnico_id')->nullable()->constrained('tecnicos')->nullOnDelete();
            $table->boolean('is_estoque')->default(true);
            $table->foreignId('status_rastreador_id')->nullable()->constrained('status_rastreadores')->nullOnDelete();
            $table->timestamp('criado_em')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rastreadores');
        Schema::dropIfExists('chips');
        Schema::dropIfExists('tecnicos');
        Schema::dropIfExists('tipo_veiculos');
    }
};
