<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // No default users: create the owner with `php artisan cms:create-owner`.
        $this->call(CmsSeeder::class);
    }
}
