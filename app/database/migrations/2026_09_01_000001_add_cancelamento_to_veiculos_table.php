<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('veiculos', function (Blueprint $table): void {
            $table->text('motivo_cancelamento')->nullable()->after('data_retirada');
            $table->dateTime('cancelado_em')->nullable()->after('motivo_cancelamento');
            $table->foreignId('cancelado_por')->nullable()->after('cancelado_em')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('veiculos', function (Blueprint $table): void {
            $table->dropForeign(['cancelado_por']);
            $table->dropColumn(['motivo_cancelamento', 'cancelado_em', 'cancelado_por']);
        });
    }
};
