<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracksolid_importacoes', function (Blueprint $table): void {
            $table->id();
            $table->string('arquivo');
            $table->string('sha256', 64)->unique();
            $table->unsignedInteger('total_registros')->default(0);
            $table->unsignedInteger('total_tags')->default(0);
            $table->unsignedInteger('total_rastreadores')->default(0);
            $table->timestamp('importado_em')->useCurrent();
            $table->json('resumo')->nullable();
            $table->timestamps();
        });

        Schema::create('tracksolid_dispositivos_importados', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('importacao_id')->constrained('tracksolid_importacoes')->cascadeOnDelete();
            $table->unsignedInteger('linha');
            $table->string('conta')->nullable();
            $table->string('cliente_nome')->nullable();
            $table->text('dispositivo_nome')->nullable();
            $table->string('imei', 50);
            $table->string('modelo', 100)->nullable();
            $table->boolean('is_tag')->default(false);
            $table->string('sim', 100)->nullable();
            $table->string('iccid', 100)->nullable();
            $table->string('placa_informada', 50)->nullable();
            $table->string('placa_extraida', 20)->nullable();
            $table->string('placa', 20)->nullable();
            $table->string('grupo')->nullable();
            $table->string('data_ativacao')->nullable();
            $table->string('expiracao_assinatura')->nullable();
            $table->string('data_instalacao')->nullable();
            $table->json('dados_brutos');
            $table->timestamps();

            $table->unique(['importacao_id', 'linha']);
            $table->index(['importacao_id', 'is_tag']);
            $table->index('imei');
            $table->index('iccid');
            $table->index('placa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracksolid_dispositivos_importados');
        Schema::dropIfExists('tracksolid_importacoes');
    }
};
