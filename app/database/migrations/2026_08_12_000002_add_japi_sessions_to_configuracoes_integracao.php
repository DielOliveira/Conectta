<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes_integracao', function (Blueprint $table): void {
            $table->string('japi_sessao_cobrancas', 32)->nullable()->after('client_id');
            $table->string('japi_sessao_os_campo', 32)->nullable()->after('japi_sessao_cobrancas');
            $table->string('japi_sessao_os_manutencao', 32)->nullable()->after('japi_sessao_os_campo');
        });

        DB::table('configuracoes_integracao')->where('integracao', 'japi')->update([
            'japi_sessao_cobrancas' => DB::raw('client_id'),
            'japi_sessao_os_campo' => DB::raw('client_id'),
            'japi_sessao_os_manutencao' => DB::raw('client_id'),
        ]);
    }

    public function down(): void
    {
        Schema::table('configuracoes_integracao', function (Blueprint $table): void {
            $table->dropColumn(['japi_sessao_cobrancas', 'japi_sessao_os_campo', 'japi_sessao_os_manutencao']);
        });
    }
};
