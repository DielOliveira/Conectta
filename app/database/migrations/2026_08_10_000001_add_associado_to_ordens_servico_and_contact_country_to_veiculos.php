<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('veiculos', function (Blueprint $table): void {
            $table->string('contato_pais', 5)->default('BR')->after('contato');
        });

        Schema::table('ordens_servico', function (Blueprint $table): void {
            $table->boolean('associado')->default(false)->after('veiculo_id');
        });
    }

    public function down(): void
    {
        Schema::table('ordens_servico', fn (Blueprint $table) => $table->dropColumn('associado'));
        Schema::table('veiculos', fn (Blueprint $table) => $table->dropColumn('contato_pais'));
    }
};
