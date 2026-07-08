<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chips', function (Blueprint $table): void {
            if (Schema::hasColumn('chips', 'iccid') && ! Schema::hasColumn('chips', 'numero_chip')) {
                $table->renameColumn('iccid', 'numero_chip');
            }
        });

        Schema::table('chips', function (Blueprint $table): void {
            if (! Schema::hasColumn('chips', 'iccid')) {
                $table->string('iccid', 20)->nullable()->after('numero_chip')->unique();
            }
        });
    }

    public function down(): void
    {
        Schema::table('chips', function (Blueprint $table): void {
            if (Schema::hasColumn('chips', 'iccid')) {
                $table->dropUnique(['iccid']);
                $table->dropColumn('iccid');
            }
        });

        Schema::table('chips', function (Blueprint $table): void {
            if (Schema::hasColumn('chips', 'numero_chip') && ! Schema::hasColumn('chips', 'iccid')) {
                $table->renameColumn('numero_chip', 'iccid');
            }
        });
    }
};
