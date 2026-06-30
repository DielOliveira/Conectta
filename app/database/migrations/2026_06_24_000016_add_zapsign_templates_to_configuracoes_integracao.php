<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes_integracao', function (Blueprint $table): void {
            if (! Schema::hasColumn('configuracoes_integracao', 'template_principal_id')) {
                $table->string('template_principal_id', 255)->nullable()->after('timeout');
            }

            if (! Schema::hasColumn('configuracoes_integracao', 'template_aditivo_id')) {
                $table->string('template_aditivo_id', 255)->nullable()->after('template_principal_id');
            }

            if (! Schema::hasColumn('configuracoes_integracao', 'template_comodato_id')) {
                $table->string('template_comodato_id', 255)->nullable()->after('template_aditivo_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes_integracao', function (Blueprint $table): void {
            $table->dropColumn(['template_principal_id', 'template_aditivo_id', 'template_comodato_id']);
        });
    }
};