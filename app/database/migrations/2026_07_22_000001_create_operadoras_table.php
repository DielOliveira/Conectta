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
    private const OPERADORAS = [
        1 => 'ALGAR 5 OP',
        2 => 'ARQIA',
        3 => 'ARQIA DUAL C',
        4 => 'CLARO',
        5 => 'CONNECT ONE CLARO',
        6 => 'TIM',
        7 => 'VIVO',
    ];

    public function up(): void
    {
        Schema::create('operadoras', function (Blueprint $table): void {
            $table->id();
            $table->string('nome', 50)->unique();
            $table->timestamps();
        });

        $agora = now();

        DB::table('operadoras')->insert(
            collect(self::OPERADORAS)
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
            $table->foreignId('operadora_id')
                ->nullable()
                ->after('operadora')
                ->constrained('operadoras')
                ->nullOnDelete();
        });

        foreach (self::OPERADORAS as $id => $nome) {
            DB::table('chips')
                ->whereRaw('UPPER(TRIM(operadora)) = ?', [$nome])
                ->update(['operadora_id' => $id]);
        }

        DB::table('chips')
            ->whereRaw('UPPER(TRIM(operadora)) = ?', ['ALGAR'])
            ->update(['operadora_id' => 1]);
    }

    public function down(): void
    {
        Schema::table('chips', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('operadora_id');
        });

        Schema::dropIfExists('operadoras');
    }
};
