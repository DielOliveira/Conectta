<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('acao', 120);
            $table->string('entidade_tipo', 120);
            $table->unsignedBigInteger('entidade_id')->nullable();
            $table->text('descricao');
            $table->json('antes')->nullable();
            $table->json('depois')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('contexto')->nullable();
            $table->timestamps();

            $table->index(['acao', 'created_at']);
            $table->index(['entidade_tipo', 'entidade_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
