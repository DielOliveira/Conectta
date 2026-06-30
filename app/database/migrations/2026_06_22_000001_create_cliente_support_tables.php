<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('status_clientes', function (Blueprint $table): void {
            $table->id();
            $table->string('label', 50);
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('status_contratos', function (Blueprint $table): void {
            $table->id();
            $table->string('label', 50);
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cliente_origens', function (Blueprint $table): void {
            $table->id();
            $table->string('label', 50);
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('estados', function (Blueprint $table): void {
            $table->id();
            $table->string('label', 50);
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('vendedores', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('numr')->nullable();
            $table->string('nome', 50);
            $table->timestamps();
        });

        Schema::create('status_rastreadores', function (Blueprint $table): void {
            $table->id();
            $table->string('label', 50);
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_rastreadores');
        Schema::dropIfExists('vendedores');
        Schema::dropIfExists('estados');
        Schema::dropIfExists('cliente_origens');
        Schema::dropIfExists('status_contratos');
        Schema::dropIfExists('status_clientes');
    }
};
