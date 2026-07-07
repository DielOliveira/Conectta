<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Permission::query()->updateOrCreate(
            ['nome' => Permission::TECNICO],
            [
                'label' => 'Técnico',
                'modulo' => 'Administrativo',
                'acao' => 'Tecnico',
                'ordem' => 130,
            ],
        );
    }

    public function down(): void
    {
        Permission::query()
            ->where('nome', Permission::TECNICO)
            ->delete();
    }
};
