<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->string('linha_digitavel', 500)->nullable()->after('link_boleto');
            $table->text('pix_copia_cola')->nullable()->after('linha_digitavel');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn(['linha_digitavel', 'pix_copia_cola']);
        });
    }
};
