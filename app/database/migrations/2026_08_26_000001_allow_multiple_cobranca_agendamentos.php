<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, string> */
    private const HORARIOS_FIM_SEMANA = [
        'atraso_2' => '09:00:00',
        'atraso_5' => '09:05:00',
        'atraso_7' => '09:10:00',
        'atraso_10' => '09:15:00',
        'atraso_12' => '09:20:00',
        'atraso_15' => '09:25:00',
        'lembrete_vencimento' => '09:50:00',
        'boleto_7_dias' => '09:55:00',
    ];

    public function up(): void
    {
        Schema::table('cobranca_agendamentos', function (Blueprint $table): void {
            $table->dropUnique(['tipo']);
        });

        $agendamentos = DB::table('cobranca_agendamentos')
            ->whereIn('tipo', array_keys(self::HORARIOS_FIM_SEMANA))
            ->orderBy('id')
            ->get()
            ->keyBy('tipo');

        foreach (self::HORARIOS_FIM_SEMANA as $tipo => $horario) {
            $agendamento = $agendamentos->get($tipo);

            if ($agendamento === null) {
                continue;
            }

            DB::table('cobranca_agendamentos')->where('id', $agendamento->id)->update([
                'dias_semana' => json_encode([1, 2, 3, 4, 5]),
                'proxima_execucao_em' => null,
                'updated_at' => now(),
            ]);

            $fimDeSemana = (array) $agendamento;
            unset($fimDeSemana['id']);

            $fimDeSemana['horario'] = $horario;
            $fimDeSemana['dias_semana'] = json_encode([0, 6]);
            $fimDeSemana['ultima_execucao_em'] = null;
            $fimDeSemana['proxima_execucao_em'] = null;
            $fimDeSemana['ultimo_status'] = null;
            $fimDeSemana['ultima_mensagem'] = null;
            $fimDeSemana['ultima_cobranca_execucao_id'] = null;
            $fimDeSemana['created_at'] = now();
            $fimDeSemana['updated_at'] = now();

            DB::table('cobranca_agendamentos')->insert($fimDeSemana);
        }
    }

    public function down(): void
    {
        DB::table('cobranca_agendamentos')
            ->orderBy('id')
            ->get()
            ->groupBy('tipo')
            ->each(function ($agendamentos): void {
                $principal = $agendamentos->first();

                DB::table('cobranca_agendamentos')
                    ->whereIn('id', $agendamentos->skip(1)->pluck('id'))
                    ->delete();

                DB::table('cobranca_agendamentos')->where('id', $principal->id)->update([
                    'dias_semana' => json_encode([0, 1, 2, 3, 4, 5, 6]),
                    'proxima_execucao_em' => null,
                    'updated_at' => now(),
                ]);
            });

        Schema::table('cobranca_agendamentos', function (Blueprint $table): void {
            $table->unique('tipo');
        });
    }
};
