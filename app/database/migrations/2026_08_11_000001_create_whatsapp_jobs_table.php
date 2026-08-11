<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_jobs', function (Blueprint $table): void {
            $table->id();
            $table->morphs('origem');
            $table->string('etapa', 80);
            $table->string('driver', 20);
            $table->string('sessao', 32)->nullable();
            $table->string('idempotency_key', 200)->unique();
            $table->string('job_id', 100)->nullable();
            $table->string('status', 30);
            $table->string('whatsapp_message_id', 255)->nullable();
            $table->unsignedSmallInteger('tentativas')->default(0);
            $table->text('ultimo_erro')->nullable();
            $table->json('resposta')->nullable();
            $table->timestamp('enfileirado_em')->nullable();
            $table->timestamp('enviado_em')->nullable();
            $table->timestamp('falhou_em')->nullable();
            $table->timestamps();

            $table->unique(['sessao', 'job_id']);
            $table->index(['driver', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_jobs');
    }
};
