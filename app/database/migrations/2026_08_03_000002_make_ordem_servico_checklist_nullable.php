<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table): void {
            $table->boolean('check_funcionamento')->nullable()->default(null)->change();
            $table->boolean('check_pos_chave')->nullable()->default(null)->change();
        });

        DB::table('ordens_servico')->where('status', '!=', 'finalizada')->update([
            'check_funcionamento' => null,
            'check_pos_chave' => null,
            'check_bloqueio' => null,
        ]);
    }

    public function down(): void
    {
        DB::table('ordens_servico')->whereNull('check_funcionamento')->update(['check_funcionamento' => false]);
        DB::table('ordens_servico')->whereNull('check_pos_chave')->update(['check_pos_chave' => false]);

        Schema::table('ordens_servico', function (Blueprint $table): void {
            $table->boolean('check_funcionamento')->default(false)->nullable(false)->change();
            $table->boolean('check_pos_chave')->default(false)->nullable(false)->change();
        });
    }
};
