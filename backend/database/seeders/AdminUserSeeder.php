<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('ADMIN_EMAIL', '');
        $password = (string) env('ADMIN_PASSWORD', '');
        $usesPlaceholder = $password === '' || str_starts_with($password, 'CHANGE_');

        if (app()->environment('production') && ($email === '' || $usesPlaceholder)) {
            $this->command?->warn('Admin user was not seeded. Set ADMIN_EMAIL and a strong ADMIN_PASSWORD first.');

            return;
        }

        if ($email === '' || $usesPlaceholder) {
            $email = 'admin@example.com';
            $password = 'password';
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => 'IAIOI Admin',
                'password' => $password,
                'role' => 'admin',
                'status' => 'active',
            ],
        );
    }
}
