<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (env('CENTRAL_ADMIN_EMAIL') && env('CENTRAL_ADMIN_PASSWORD')) {
            $this->call(HeadOperatorSeeder::class);
        }
    }
}
