<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faturamentos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('ano');
            $table->unsignedTinyInteger('mes');
            $table->boolean('is_aberto')->default(false);
            $table->timestamps();

            $table->unique(['ano', 'mes']);
            $table->index(['ano', 'is_aberto']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faturamentos');
    }
};
