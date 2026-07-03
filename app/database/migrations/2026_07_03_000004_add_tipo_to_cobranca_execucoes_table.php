<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cobranca_execucoes', function (Blueprint $table): void {
            $table->string('tipo', 50)->nullable()->after('data_processamento');
            $table->index(['data_processamento', 'tipo']);
        });

        DB::table('cobranca_execucoes')
            ->orderBy('id')
            ->get(['id'])
            ->each(function (object $execucao): void {
                $tipos = DB::table('cobranca_envios')
                    ->where('cobranca_execucao_id', $execucao->id)
                    ->whereNotNull('tipo')
                    ->distinct()
                    ->orderBy('tipo')
                    ->pluck('tipo')
                    ->all();

                DB::table('cobranca_execucoes')
                    ->where('id', $execucao->id)
                    ->update(['tipo' => count($tipos) === 1 ? $tipos[0] : (count($tipos) > 1 ? 'multiplos' : null)]);
            });
    }

    public function down(): void
    {
        Schema::table('cobranca_execucoes', function (Blueprint $table): void {
            $table->dropIndex(['data_processamento', 'tipo']);
            $table->dropColumn('tipo');
        });
    }
};
