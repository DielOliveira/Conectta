<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private const FORNECEDORES = [
        1 => 'HINOVA',
        2 => 'TRANSMEET',
    ];

    public function up(): void
    {
        Schema::create('fornecedores', function (Blueprint $table): void {
            $table->id();
            $table->string('nome', 50)->unique();
            $table->timestamps();
        });

        $agora = now();

        DB::table('fornecedores')->insert(
            collect(self::FORNECEDORES)
                ->map(fn (string $nome, int $id): array => [
                    'id' => $id,
                    'nome' => $nome,
                    'created_at' => $agora,
                    'updated_at' => $agora,
                ])
                ->values()
                ->all(),
        );

        Schema::table('chips', function (Blueprint $table): void {
            $table->foreignId('fornecedor_id')
                ->nullable()
                ->after('fornecedor')
                ->constrained('fornecedores')
                ->nullOnDelete();
        });

        DB::table('chips')
            ->whereRaw('UPPER(TRIM(fornecedor)) = ?', ['HINOVA'])
            ->update(['fornecedor' => 'HINOVA', 'fornecedor_id' => 1]);

        DB::table('chips')
            ->whereRaw('UPPER(TRIM(fornecedor)) = ?', ['TRANSMEET'])
            ->update(['fornecedor' => 'TRANSMEET', 'fornecedor_id' => 2]);

        $this->classificarImportacoesHinova();

        DB::table('chips')
            ->whereNull('fornecedor_id')
            ->update(['fornecedor' => null]);
    }

    private function classificarImportacoesHinova(): void
    {
        if (! Schema::hasTable('inventario')) {
            return;
        }

        $inventario = DB::table('inventario')->get(['iccid', 'numero', 'imei']);
        $iccids = [];
        $numeros = [];
        $imeis = [];

        foreach ($inventario as $item) {
            if (($iccid = $this->digitos($item->iccid)) !== '') {
                $iccids[$iccid] = true;
            }

            if (($numero = $this->digitos($item->numero)) !== '') {
                $numeros[$numero] = true;
            }

            if (($imei = $this->digitos($item->imei)) !== '') {
                $imeis[$imei] = true;
            }
        }

        $imeisPorChip = DB::table('rastreadores')
            ->whereNotNull('chip_id')
            ->get(['chip_id', 'imei'])
            ->groupBy('chip_id');

        DB::table('chips')
            ->whereRaw('UPPER(TRIM(fornecedor)) = ?', ['IMPORTACAO'])
            ->orderBy('id')
            ->chunkById(500, function ($chips) use ($iccids, $numeros, $imeis, $imeisPorChip): void {
                foreach ($chips as $chip) {
                    $encontrado = isset($iccids[$this->digitos($chip->iccid)])
                        || isset($numeros[$this->digitos($chip->numero_chip)]);

                    if (! $encontrado) {
                        foreach ($imeisPorChip->get($chip->id, collect()) as $rastreador) {
                            if (isset($imeis[$this->digitos($rastreador->imei)])) {
                                $encontrado = true;

                                break;
                            }
                        }
                    }

                    DB::table('chips')
                        ->where('id', $chip->id)
                        ->update($encontrado
                            ? ['fornecedor' => 'HINOVA', 'fornecedor_id' => 1]
                            : ['fornecedor' => null, 'fornecedor_id' => null]);
                }
            });
    }

    private function digitos(mixed $valor): string
    {
        return preg_replace('/\D+/', '', trim((string) $valor)) ?? '';
    }

    public function down(): void
    {
        Schema::table('chips', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('fornecedor_id');
        });

        Schema::dropIfExists('fornecedores');
    }
};
