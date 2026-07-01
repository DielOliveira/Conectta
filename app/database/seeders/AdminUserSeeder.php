<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('ADMIN_PASSWORD');

        if (blank($password)) {
            throw new RuntimeException('Defina ADMIN_PASSWORD antes de executar o AdminUserSeeder.');
        }

        $admin = User::query()->updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@conectta.local')],
            [
                'name' => env('ADMIN_NAME', 'Administrador'),
                'password' => Hash::make($password),
                'is_admin' => true,
            ],
        );

        $admin->permissions()->sync(Permission::query()->pluck('id'));
    }
}
