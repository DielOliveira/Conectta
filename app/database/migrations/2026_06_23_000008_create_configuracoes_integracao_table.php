<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracoes_integracao', function (Blueprint $table): void {
            $table->id();
            $table->string('integracao', 50);
            $table->string('ambiente', 50)->default('producao');
            $table->string('base_url', 255)->nullable();
            $table->text('token')->nullable();
            $table->string('auth_scheme', 30)->nullable();
            $table->unsignedSmallInteger('timeout')->default(30);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique('integracao');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracoes_integracao');
    }
};
