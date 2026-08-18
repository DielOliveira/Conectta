<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordem_servico_disponibilidades', function (Blueprint $table): void {
            $table->string('tipo', 20)->default('disponibilidade')->after('tecnico_id');
            $table->index(['tecnico_id', 'data', 'tipo'], 'os_disponibilidades_tecnico_data_tipo_index');
        });
    }

    public function down(): void
    {
        Schema::table('ordem_servico_disponibilidades', function (Blueprint $table): void {
            $table->dropIndex('os_disponibilidades_tecnico_data_tipo_index');
            $table->dropColumn('tipo');
        });
    }
};
