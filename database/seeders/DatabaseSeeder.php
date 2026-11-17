<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@escolar.test'],
            [
                'name' => 'Administrador',
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'coordinador@escolar.test'],
            [
                'name' => 'Coordinador Académico',
                'role' => UserRole::Coordinator,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ],
        );

        $this->call([
            SettingsSeeder::class,
            CatalogSeeder::class,
        ]);
    }
}
