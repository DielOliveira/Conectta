<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paises', function (Blueprint $table): void {
            $table->id();
            $table->string('iso2', 2)->unique();
            $table->string('nome', 100);
            $table->string('ddi', 5);
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('clientes')
            ->where('telefone1_pais', '55')
            ->orWhereNull('telefone1_pais')
            ->orWhere('telefone1_pais', '')
            ->update(['telefone1_pais' => 'BR']);

        DB::table('clientes')
            ->where('telefone2_pais', '55')
            ->update(['telefone2_pais' => 'BR']);
    }

    public function down(): void
    {
        DB::table('clientes')
            ->where('telefone1_pais', 'BR')
            ->update(['telefone1_pais' => '55']);

        DB::table('clientes')
            ->where('telefone2_pais', 'BR')
            ->update(['telefone2_pais' => '55']);

        Schema::dropIfExists('paises');
    }
};
