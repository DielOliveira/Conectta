<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('veiculos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('status_rastreador_id')->nullable()->constrained('status_rastreadores')->nullOnDelete();
            $table->string('veiculo', 50)->nullable();
            $table->string('placa', 50)->nullable();
            $table->string('imei', 150)->nullable();
            $table->date('data_instalacao')->nullable();
            $table->date('data_retirada')->nullable();
            $table->dateTime('data_exclusao')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('veiculos');
    }
};
