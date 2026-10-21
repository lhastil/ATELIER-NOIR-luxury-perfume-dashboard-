<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // The demo and sample seeders skip themselves in production; only the
        // categories, which the product form needs, are created there.
        $this->call([
            CategorySeeder::class,
            DemoAdminSeeder::class,
            SampleProductSeeder::class,
            SampleOrderSeeder::class,
        ]);
    }
}
