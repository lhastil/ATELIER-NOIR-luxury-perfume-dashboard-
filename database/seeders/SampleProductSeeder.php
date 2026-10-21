<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Fifteen fictional perfumes used as sample catalog data (see README).
 * Products are matched by SKU, so running the seeder again neither duplicates
 * them nor overwrites changes made in the panel. A sample whose SKU was changed
 * in the panel is added again with its original SKU.
 */
class SampleProductSeeder extends Seeder
{
    public function run(): void
    {
        // The products need their categories, so this seeder also works on its own.
        $this->call(CategorySeeder::class);

        $categories = Category::whereIn('name', CategorySeeder::NAMES)->pluck('id', 'name');

        foreach ($this->products() as [$sku, $name, $brand, $category, $concentration, $volume, $price, $stock, $active, $description]) {
            Product::firstOrCreate(['sku' => $sku], [
                'category_id' => $categories[$category],
                'name' => $name,
                'brand' => $brand,
                'concentration' => $concentration,
                'volume_ml' => $volume,
                'price' => $price,
                'stock' => $stock,
                'is_active' => $active,
                'description' => $description,
            ]);
        }
    }

    /**
     * @return list<array{string, string, string, string, string, int, string, int, bool, string}>
     */
    private function products(): array
    {
        return [
            ['AN-FLR-001', 'Rose Nocturne', 'Atelier Noir', 'Floral', 'Eau de Parfum', 50, '128.00', 24, true, 'Damask rose and black pepper over a soft musk base.'],
            ['AN-FLR-002', 'Jardin d’Iris', 'Maison Ivrelle', 'Floral', 'Eau de Toilette', 100, '96.00', 31, true, 'Powdery iris with pear and white cedar.'],
            ['AN-FLR-003', 'Fleur Blanche', 'Ombrevale', 'Floral', 'Eau de Parfum', 75, '112.00', 8, true, 'Tuberose and jasmine softened with orange blossom.'],
            ['AN-WDY-001', 'Cèdre Ombré', 'Atelier Noir', 'Woody', 'Parfum', 50, '185.00', 12, true, 'Smoked cedar, vetiver and a trace of leather.'],
            ['AN-WDY-002', 'Bois de Minuit', 'Nordhavn', 'Woody', 'Eau de Parfum', 100, '142.00', 19, true, 'Sandalwood and cardamom with a dry oud finish.'],
            ['AN-WDY-003', 'Forêt Sauvage', 'Maison Ivrelle', 'Woody', 'Eau de Toilette', 50, '84.00', 0, false, 'Pine needles, moss and crisp bergamot.'],
            ['AN-AMB-001', 'Ambre Royal', 'Atelier Noir', 'Amber', 'Parfum', 50, '210.00', 6, true, 'Labdanum, benzoin and warm vanilla resin.'],
            ['AN-AMB-002', 'Encens Doré', 'Solenne & Fils', 'Amber', 'Eau de Parfum', 75, '134.00', 15, true, 'Frankincense and saffron over golden amber.'],
            ['AN-AMB-003', 'Velours Épicé', 'Ombrevale', 'Amber', 'Eau de Parfum', 100, '158.00', 22, true, 'Cinnamon, clove and plum on a velvet base.'],
            ['AN-FRS-001', 'Brise Marine', 'Nordhavn', 'Fresh', 'Eau de Toilette', 100, '78.00', 40, true, 'Sea salt, grapefruit and driftwood.'],
            ['AN-FRS-002', 'Agrumes Clairs', 'Solenne & Fils', 'Fresh', 'Eau de Cologne', 100, '65.00', 36, true, 'Lemon, neroli and petitgrain.'],
            ['AN-FRS-003', 'Thé Vert Matin', 'Maison Ivrelle', 'Fresh', 'Eau de Toilette', 50, '72.00', 3, true, 'Green tea, mint and white musk.'],
            ['AN-GRM-001', 'Vanille Noire', 'Atelier Noir', 'Gourmand', 'Eau de Parfum', 50, '138.00', 17, true, 'Bourbon vanilla, tonka bean and dark rum.'],
            ['AN-GRM-002', 'Praline Fumée', 'Ombrevale', 'Gourmand', 'Eau de Parfum', 75, '124.00', 11, true, 'Roasted hazelnut and caramel with a smoky edge.'],
            ['AN-GRM-003', 'Cacao Velouté', 'Solenne & Fils', 'Gourmand', 'Parfum', 30, '98.00', 0, false, 'Dark cocoa, patchouli and orris.'],
        ];
    }
}
