<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lytex_webhook_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('configuracao_integracao_id')->nullable()->constrained('configuracoes_integracao')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('webhook_type', 80)->nullable();
            $table->string('signature', 255)->nullable();
            $table->string('invoice_external_id', 120)->nullable();
            $table->string('reference_id', 120)->nullable();
            $table->string('status', 80)->nullable();
            $table->json('payload')->nullable();
            $table->boolean('is_valid')->default(false);
            $table->boolean('processed')->default(false);
            $table->string('message', 1000)->nullable();
            $table->timestamps();

            $table->index(['webhook_type', 'created_at']);
            $table->index('invoice_external_id');
            $table->index('reference_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lytex_webhook_logs');
    }
};