<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ([
            ['label' => 'Expirado', 'order' => 5],
            ['label' => 'Deletado', 'order' => 6],
            ['label' => 'Cancelado', 'order' => 7],
        ] as $status) {
            DB::table('status_contratos')->updateOrInsert(
                ['label' => $status['label']],
                [
                    'order' => $status['order'],
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        DB::table('status_contratos')
            ->whereIn('label', ['Expirado', 'Deletado', 'Cancelado'])
            ->delete();
    }
};
