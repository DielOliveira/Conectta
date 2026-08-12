<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tecnicos', function (Blueprint $table): void {
            $table->string('agenda_token_hash', 64)->nullable()->unique()->after('is_ativo');
            $table->text('agenda_token_credencial')->nullable()->after('agenda_token_hash');
        });
    }

    public function down(): void
    {
        Schema::table('tecnicos', function (Blueprint $table): void {
            $table->dropUnique(['agenda_token_hash']);
            $table->dropColumn(['agenda_token_hash', 'agenda_token_credencial']);
        });
    }
};
