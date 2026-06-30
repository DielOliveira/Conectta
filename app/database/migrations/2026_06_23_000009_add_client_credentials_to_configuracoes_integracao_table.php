<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes_integracao', function (Blueprint $table): void {
            $table->string('client_id', 255)->nullable()->after('base_url');
            $table->text('client_secret')->nullable()->after('client_id');
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes_integracao', function (Blueprint $table): void {
            $table->dropColumn(['client_id', 'client_secret']);
        });
    }
};
