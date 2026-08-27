<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table): void {
            $table->boolean('bloqueio')->nullable()->after('local_instalacao');
        });
    }

    public function down(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table): void {
            $table->dropColumn('bloqueio');
        });
    }
};
