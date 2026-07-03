<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cobranca_agendamentos', function (Blueprint $table): void {
            $table->id();
            $table->string('tipo', 50)->unique();
            $table->boolean('ativo')->default(false);
            $table->time('horario')->default('08:00:00');
            $table->json('dias_semana')->nullable();
            $table->boolean('dry_run')->default(true);
            $table->boolean('enviar_whatsapp')->default(false);
            $table->unsignedInteger('limite')->nullable();
            $table->timestamp('ultima_execucao_em')->nullable();
            $table->timestamp('proxima_execucao_em')->nullable();
            $table->string('ultimo_status', 30)->nullable();
            $table->text('ultima_mensagem')->nullable();
            $table->foreignId('ultima_cobranca_execucao_id')->nullable()->constrained('cobranca_execucoes')->nullOnDelete();
            $table->timestamps();

            $table->index(['ativo', 'proxima_execucao_em']);
            $table->index('tipo');
        });

        Schema::table('cobranca_execucoes', function (Blueprint $table): void {
            $table->foreignId('cobranca_agendamento_id')
                ->nullable()
                ->after('id')
                ->constrained('cobranca_agendamentos')
                ->nullOnDelete();
        });

        foreach ($this->tiposPadrao() as $tipo => $horario) {
            DB::table('cobranca_agendamentos')->insert([
                'tipo' => $tipo,
                'ativo' => false,
                'horario' => $horario,
                'dias_semana' => json_encode([0, 1, 2, 3, 4, 5, 6]),
                'dry_run' => true,
                'enviar_whatsapp' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('cobranca_execucoes', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('cobranca_agendamento_id');
        });

        Schema::dropIfExists('cobranca_agendamentos');
    }

    /**
     * @return array<string, string>
     */
    private function tiposPadrao(): array
    {
        return [
            'boleto_7_dias' => '08:00:00',
            'lembrete_vencimento' => '09:00:00',
            'atraso_2' => '09:30:00',
            'atraso_5' => '10:00:00',
            'atraso_7' => '10:30:00',
            'atraso_10' => '11:00:00',
            'atraso_12' => '11:30:00',
            'atraso_15' => '12:00:00',
        ];
    }
};
