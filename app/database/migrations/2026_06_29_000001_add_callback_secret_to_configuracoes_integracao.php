<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes_integracao', function (Blueprint $table): void {
            $table->text('callback_secret')->nullable()->after('client_secret');
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes_integracao', function (Blueprint $table): void {
            $table->dropColumn('callback_secret');
        });
    }
};