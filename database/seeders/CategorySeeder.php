<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * The default perfume families products are grouped by, provided on a fresh
 * installation. Administrators can add, rename or delete categories in the
 * panel afterwards. Running it again leaves existing categories untouched.
 */
class CategorySeeder extends Seeder
{
    public const NAMES = ['Floral', 'Woody', 'Amber', 'Fresh', 'Gourmand'];

    public function run(): void
    {
        foreach (self::NAMES as $name) {
            Category::firstOrCreate(['name' => $name]);
        }
    }
}
