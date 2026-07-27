<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lancamentos', function (Blueprint $table): void {
            $table->timestamp('invalidado_em')->nullable()->after('log');
            $table->string('motivo_invalidacao', 500)->nullable()->after('invalidado_em');
            $table->index(
                ['cliente_id', 'mes_referencia', 'ano_referencia', 'invalidado_em'],
                'lancamentos_referencia_invalidado_idx',
            );
        });

        DB::table('lancamentos')
            ->where('observacao', 'like', '%Neutralizado em saneamento de duplicidade;%')
            ->update([
                'invalidado_em' => now(),
                'motivo_invalidacao' => 'Duplicidade neutralizada no saneamento de boletos Lytex.',
            ]);
    }

    public function down(): void
    {
        Schema::table('lancamentos', function (Blueprint $table): void {
            $table->dropIndex('lancamentos_referencia_invalidado_idx');
            $table->dropColumn(['invalidado_em', 'motivo_invalidacao']);
        });
    }
};
