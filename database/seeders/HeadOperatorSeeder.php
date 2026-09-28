<?php

namespace Database\Seeders;

use App\Models\Central\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class HeadOperatorSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('CENTRAL_ADMIN_EMAIL');
        $password = env('CENTRAL_ADMIN_PASSWORD');

        if (! $email || ! $password) {
            throw new RuntimeException('CENTRAL_ADMIN_EMAIL and CENTRAL_ADMIN_PASSWORD must be set before seeding the head operator.');
        }

        AdminUser::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => env('CENTRAL_ADMIN_NAME', 'Head Operator'),
                'password' => Hash::make($password),
                'role' => 'head_operator',
                'is_active' => true,
            ],
        );
    }
}
