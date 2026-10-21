<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * The perfume families products are grouped by. Categories are not managed in
 * the panel, so this seeder also provides them on a fresh installation.
 * Running it again leaves existing categories untouched.
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
