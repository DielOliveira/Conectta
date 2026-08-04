<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table): void {
            $table->dropColumn('atendimento_desejado_em');
        });
    }

    public function down(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table): void {
            $table->dateTime('atendimento_desejado_em')->nullable()->after('disponibilidade_id');
        });
    }
};
