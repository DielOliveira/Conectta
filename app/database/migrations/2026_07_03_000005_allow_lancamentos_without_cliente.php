<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('lancamentos', function (Blueprint $table): void {
                $table->unsignedBigInteger('cliente_id')->nullable()->change();
            });

            return;
        }

        DB::statement('ALTER TABLE lancamentos MODIFY cliente_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        if ((int) DB::table('lancamentos')->whereNull('cliente_id')->count() > 0) {
            throw new RuntimeException('Existem lancamentos sem cliente. Remova ou vincule esses ajustes antes de reverter esta migration.');
        }

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('lancamentos', function (Blueprint $table): void {
                $table->unsignedBigInteger('cliente_id')->nullable(false)->change();
            });

            return;
        }

        DB::statement('ALTER TABLE lancamentos MODIFY cliente_id BIGINT UNSIGNED NOT NULL');
    }
};
