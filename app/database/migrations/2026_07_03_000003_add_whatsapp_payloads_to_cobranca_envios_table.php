<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cobranca_envios', function (Blueprint $table): void {
            $table->longText('whatsapp_payload')->nullable()->after('erro');
            $table->longText('whatsapp_response')->nullable()->after('whatsapp_payload');
        });
    }

    public function down(): void
    {
        Schema::table('cobranca_envios', function (Blueprint $table): void {
            $table->dropColumn(['whatsapp_payload', 'whatsapp_response']);
        });
    }
};
