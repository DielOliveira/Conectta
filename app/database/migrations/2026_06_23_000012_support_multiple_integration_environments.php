<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes_integracao', function (Blueprint $table): void {
            $table->dropUnique('configuracoes_integracao_integracao_unique');
            $table->unique(['integracao', 'ambiente']);
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes_integracao', function (Blueprint $table): void {
            $table->dropUnique('configuracoes_integracao_integracao_ambiente_unique');
            $table->unique('integracao');
        });
    }
};
