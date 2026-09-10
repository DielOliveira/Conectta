<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table): void {
            $table->string('motivo_improdutividade')->nullable()->after('motivo_pendencia');
            $table->text('descricao_improdutividade')->nullable()->after('motivo_improdutividade');
            $table->dateTime('improdutiva_em')->nullable()->after('descricao_improdutividade');
            $table->foreignId('improdutiva_por')->nullable()->after('improdutiva_em')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('improdutiva_por');
            $table->dropColumn(['motivo_improdutividade', 'descricao_improdutividade', 'improdutiva_em']);
        });
    }
};
