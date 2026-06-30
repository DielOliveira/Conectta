<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@conectta.local')],
            [
                'name' => env('ADMIN_NAME', 'Administrador'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'admin123')),
                'is_admin' => true,
            ],
        );

        $admin->permissions()->sync(Permission::query()->pluck('id'));
    }
}