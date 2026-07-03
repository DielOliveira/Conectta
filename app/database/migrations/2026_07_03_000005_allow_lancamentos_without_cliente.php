<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE lancamentos MODIFY cliente_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        if ((int) DB::table('lancamentos')->whereNull('cliente_id')->count() > 0) {
            throw new RuntimeException('Existem lancamentos sem cliente. Remova ou vincule esses ajustes antes de reverter esta migration.');
        }

        DB::statement('ALTER TABLE lancamentos MODIFY cliente_id BIGINT UNSIGNED NOT NULL');
    }
};
