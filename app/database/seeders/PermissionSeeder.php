<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Permission::catalogo() as $nome => $dados) {
            Permission::query()->updateOrCreate(
                ['nome' => $nome],
                [
                    'label' => $dados['label'],
                    'modulo' => $dados['modulo'],
                    'acao' => $dados['acao'],
                    'ordem' => $dados['ordem'],
                ],
            );
        }
    }
}