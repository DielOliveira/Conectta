<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $ativoId = DB::table('status_rastreadores')->where('label', 'Ativo')->value('id');

        if (! $ativoId) {
            $this->adicionarColunaChip();

            return;
        }

        $this->adicionarColunaChip();

        $chipsMigrados = [];

        DB::table('veiculos')
            ->whereNull('data_exclusao')
            ->where('status_rastreador_id', $ativoId)
            ->whereNotNull('rastreador_id')
            ->whereNotNull('chip_id')
            ->orderBy('chip_id')
            ->orderByDesc('data_instalacao')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get(['rastreador_id', 'chip_id'])
            ->each(function (object $veiculo) use (&$chipsMigrados): void {
                if (isset($chipsMigrados[$veiculo->chip_id])) {
                    return;
                }

                DB::table('rastreadores')
                    ->where('id', $veiculo->rastreador_id)
                    ->whereNull('chip_id')
                    ->update(['chip_id' => $veiculo->chip_id]);

                $chipsMigrados[$veiculo->chip_id] = true;
            });
    }

    public function down(): void
    {
        Schema::table('rastreadores', function (Blueprint $table): void {
            if (Schema::hasColumn('rastreadores', 'chip_id')) {
                $table->dropConstrainedForeignId('chip_id');
            }
        });
    }

    private function adicionarColunaChip(): void
    {
        Schema::table('rastreadores', function (Blueprint $table): void {
            if (! Schema::hasColumn('rastreadores', 'chip_id')) {
                $table->foreignId('chip_id')
                    ->nullable()
                    ->after('tecnico_id')
                    ->constrained('chips')
                    ->nullOnDelete()
                    ->unique();
            }
        });
    }
};
