<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lancamentos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('numr')->nullable();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->date('data_lancamento')->nullable();
            $table->decimal('valor_planejado', 12, 2)->nullable();
            $table->decimal('valor_efetivado', 12, 2)->nullable();
            $table->string('numero_boleto', 500)->nullable();
            $table->text('observacao')->nullable();
            $table->boolean('is_baixado')->default(false);
            $table->unsignedTinyInteger('mes_referencia')->nullable();
            $table->unsignedSmallInteger('ano_referencia')->nullable();
            $table->timestamp('time_stamp')->nullable();
            $table->text('log')->nullable();
            $table->timestamps();

            $table->index(['cliente_id', 'mes_referencia', 'ano_referencia']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lancamentos');
    }
};
