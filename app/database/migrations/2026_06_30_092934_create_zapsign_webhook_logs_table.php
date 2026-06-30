<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zapsign_webhook_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('configuracao_integracao_id')->nullable()->constrained('configuracoes_integracao')->nullOnDelete();
            $table->foreignId('contrato_id')->nullable()->constrained('contratos')->nullOnDelete();
            $table->string('event_type', 100)->nullable();
            $table->string('doc_token', 255)->nullable();
            $table->string('status', 100)->nullable();
            $table->json('payload');
            $table->boolean('is_valid')->default(false);
            $table->boolean('processed')->default(false);
            $table->string('message', 500)->nullable();
            $table->timestamps();

            $table->index(['doc_token', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zapsign_webhook_logs');
    }
};
