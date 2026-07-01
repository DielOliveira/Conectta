<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table): void {
            $table->string('telefone1_pais', 5)->default('55')->after('telefone1');
            $table->string('telefone2_pais', 5)->nullable()->after('telefone2');
        });

        DB::table('clientes')
            ->whereNull('telefone1_pais')
            ->orWhere('telefone1_pais', '')
            ->update(['telefone1_pais' => '55']);
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table): void {
            $table->dropColumn(['telefone1_pais', 'telefone2_pais']);
        });
    }
};
