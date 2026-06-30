<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contratos', function (Blueprint $table): void {
            $table->json('dados')->nullable()->after('doc_token');
        });
    }

    public function down(): void
    {
        Schema::table('contratos', function (Blueprint $table): void {
            $table->dropColumn('dados');
        });
    }
};
