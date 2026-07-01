<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chips', function (Blueprint $table): void {
            $table->foreignId('status_rastreador_id')
                ->nullable()
                ->after('tecnico_id')
                ->constrained('status_rastreadores')
                ->nullOnDelete();
        });

        $disponivelId = DB::table('status_rastreadores')
            ->where('label', 'Disponivel')
            ->value('id');

        if ($disponivelId) {
            DB::table('chips')
                ->whereNull('status_rastreador_id')
                ->update(['status_rastreador_id' => $disponivelId]);
        }
    }

    public function down(): void
    {
        Schema::table('chips', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('status_rastreador_id');
        });
    }
};
