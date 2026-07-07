<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Permission::query()->updateOrCreate(
            ['nome' => Permission::COORDENADOR],
            [
                'label' => 'Coordenador',
                'modulo' => 'Administrativo',
                'acao' => 'Coordenador',
                'ordem' => 70,
            ],
        );

        Permission::query()
            ->where('nome', Permission::TECNICO)
            ->update(['ordem' => 140]);
    }

    public function down(): void
    {
        Permission::query()
            ->where('nome', Permission::COORDENADOR)
            ->delete();

        Permission::query()
            ->where('nome', Permission::TECNICO)
            ->update(['ordem' => 130]);
    }
};
