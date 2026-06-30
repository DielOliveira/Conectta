<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('veiculos', function (Blueprint $table): void {
            $table->foreignId('tipo_veiculo_id')->nullable()->after('status_rastreador_id')->constrained('tipo_veiculos')->nullOnDelete();
            $table->foreignId('rastreador_id')->nullable()->after('tipo_veiculo_id')->constrained('rastreadores')->nullOnDelete();
            $table->foreignId('chip_id')->nullable()->after('rastreador_id')->constrained('chips')->nullOnDelete();
            $table->foreignId('tecnico_instala_id')->nullable()->after('chip_id')->constrained('tecnicos')->nullOnDelete();
            $table->foreignId('tecnico_remocao_id')->nullable()->after('tecnico_instala_id')->constrained('tecnicos')->nullOnDelete();
            $table->string('cor', 50)->nullable()->after('placa');
            $table->string('ano', 50)->nullable()->after('cor');
            $table->string('login', 50)->nullable()->after('data_instalacao');
            $table->string('senha', 50)->nullable()->after('login');
            $table->string('tecnico_remocao', 50)->nullable()->after('senha');
            $table->string('instalador', 50)->nullable()->after('tecnico_remocao');
            $table->decimal('valor_instalacao', 12, 2)->nullable()->after('instalador');
            $table->string('associado', 500)->nullable()->after('valor_instalacao');
            $table->string('contato', 50)->nullable()->after('associado');
            $table->text('observacao')->nullable()->after('contato');
        });
    }

    public function down(): void
    {
        Schema::table('veiculos', function (Blueprint $table): void {
            $table->dropForeign(['tipo_veiculo_id']);
            $table->dropForeign(['rastreador_id']);
            $table->dropForeign(['chip_id']);
            $table->dropForeign(['tecnico_instala_id']);
            $table->dropForeign(['tecnico_remocao_id']);

            $table->dropColumn([
                'tipo_veiculo_id',
                'rastreador_id',
                'chip_id',
                'tecnico_instala_id',
                'tecnico_remocao_id',
                'cor',
                'ano',
                'login',
                'senha',
                'tecnico_remocao',
                'instalador',
                'valor_instalacao',
                'associado',
                'contato',
                'observacao',
            ]);
        });
    }
};
