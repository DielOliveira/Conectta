<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tipo_contratos')) {
            Schema::create('tipo_contratos', function (Blueprint $table): void {
                $table->id();
                $table->string('label', 50);
                $table->integer('order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('contratos')) {
            Schema::create('contratos', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('veiculo_id')->constrained('veiculos')->cascadeOnDelete();
                $table->foreignId('tipo_contrato_id')->constrained('tipo_contratos')->restrictOnDelete();
                $table->foreignId('status_contrato_id')->nullable()->constrained('status_contratos')->nullOnDelete();
                $table->string('doc_token', 255)->nullable();
                $table->timestamps();

                $table->index(['veiculo_id', 'tipo_contrato_id']);
            });
        }

        $now = now();
        foreach ([
            ['label' => 'Principal', 'order' => 1],
            ['label' => 'Aditivo', 'order' => 2],
            ['label' => 'Comodato', 'order' => 3],
        ] as $tipo) {
            DB::table('tipo_contratos')->updateOrInsert(
                ['label' => $tipo['label']],
                ['order' => $tipo['order'], 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contratos');
        Schema::dropIfExists('tipo_contratos');
    }
};