<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tokens_lytex', function (Blueprint $table): void {
            $table->foreignId('configuracao_integracao_id')
                ->nullable()
                ->after('id')
                ->constrained('configuracoes_integracao')
                ->cascadeOnDelete();

            $table->index('configuracao_integracao_id');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->string('link_checkout', 1000)->nullable()->after('hash_id');
            $table->string('link_boleto', 1000)->nullable()->after('link_checkout');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn(['link_checkout', 'link_boleto']);
        });

        Schema::table('tokens_lytex', function (Blueprint $table): void {
            $table->dropForeign(['configuracao_integracao_id']);
            $table->dropIndex(['configuracao_integracao_id']);
            $table->dropColumn('configuracao_integracao_id');
        });
    }
};