<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Forty sample orders placed by the sample customers (see README). Orders are
 * matched by number; items are added only when an order is first created, so
 * running the seeder again neither duplicates items nor resets a status changed
 * in the panel. Order dates count back from the day the order is first seeded.
 */
class SampleOrderSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('Skipped: sample orders are never created in production.');

            return;
        }

        // Orders need their customers and products, so this seeder also works on its own.
        $this->call([CustomerSeeder::class, SampleProductSeeder::class]);

        $customers = Customer::pluck('id', 'email');
        $products = Product::all()->keyBy('sku');

        foreach ($this->orders() as [$number, $email, $status, $daysAgo, $items]) {
            // An order and its items are saved together, so an interrupted run never
            // leaves an order without its items.
            DB::transaction(function () use ($customers, $products, $number, $email, $status, $daysAgo, $items) {
                $order = Order::firstOrCreate(['number' => $number], [
                    'customer_id' => $customers[$email],
                    'status' => $status,
                    'placed_at' => today()->subDays($daysAgo)->addHours(9 + $daysAgo % 10),
                ]);

                if (! $order->wasRecentlyCreated) {
                    return;
                }

                // The product's details are copied as they are when the order is placed.
                foreach ($items as [$sku, $quantity]) {
                    $product = $products[$sku];

                    $order->items()->create([
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'product_sku' => $product->sku,
                        'unit_price' => $product->price,
                        'quantity' => $quantity,
                    ]);
                }
            });
        }
    }

    /**
     * Number, customer email, status, days ago and [SKU, quantity] items.
     *
     * @return list<array{string, string, string, int, list<array{string, int}>}>
     */
    private function orders(): array
    {
        return [
            ['ORD-10001', 'iris.van.dijk@example.test', 'delivered', 118, [['AN-FRS-003', 2], ['AN-FRS-001', 1]]],
            ['ORD-10002', 'arthur.bennett@example.test', 'delivered', 115, [['AN-FRS-002', 1], ['AN-AMB-003', 1]]],
            ['ORD-10003', 'ines.duval@example.test', 'delivered', 112, [['AN-GRM-002', 1]]],
            ['ORD-10004', 'gabriel.santos@example.test', 'delivered', 109, [['AN-FLR-002', 1]]],
            ['ORD-10005', 'julien.moreau@example.test', 'cancelled', 106, [['AN-AMB-003', 1], ['AN-AMB-001', 1], ['AN-GRM-002', 1]]],
            ['ORD-10006', 'clara.fontaine@example.test', 'delivered', 103, [['AN-AMB-002', 1], ['AN-FLR-003', 2], ['AN-AMB-001', 2]]],
            ['ORD-10007', 'amelie.laurent@example.test', 'delivered', 100, [['AN-FRS-003', 1]]],
            ['ORD-10008', 'clara.fontaine@example.test', 'delivered', 97, [['AN-FRS-002', 1], ['AN-AMB-001', 2], ['AN-GRM-001', 2]]],
            ['ORD-10009', 'gabriel.santos@example.test', 'delivered', 94, [['AN-FLR-002', 1]]],
            ['ORD-10010', 'freya.nielsen@example.test', 'delivered', 91, [['AN-GRM-001', 1], ['AN-FRS-003', 1]]],
            ['ORD-10011', 'amelie.laurent@example.test', 'delivered', 88, [['AN-FRS-003', 2], ['AN-FRS-002', 1]]],
            ['ORD-10012', 'oliver.hart@example.test', 'delivered', 85, [['AN-FRS-003', 1], ['AN-AMB-001', 2]]],
            ['ORD-10013', 'matteo.bianchi@example.test', 'delivered', 82, [['AN-FLR-003', 1], ['AN-FRS-002', 2]]],
            ['ORD-10014', 'hugo.lefevre@example.test', 'delivered', 79, [['AN-WDY-001', 1]]],
            ['ORD-10015', 'camille.girard@example.test', 'delivered', 76, [['AN-GRM-001', 1]]],
            ['ORD-10016', 'maya.okafor@example.test', 'delivered', 73, [['AN-AMB-002', 1], ['AN-AMB-003', 1]]],
            ['ORD-10017', 'lea.rousseau@example.test', 'delivered', 70, [['AN-AMB-002', 1], ['AN-FRS-001', 1]]],
            ['ORD-10018', 'nora.haddad@example.test', 'cancelled', 67, [['AN-FRS-001', 2], ['AN-WDY-002', 1]]],
            ['ORD-10019', 'sofia.lindqvist@example.test', 'delivered', 64, [['AN-WDY-001', 1], ['AN-FLR-003', 1]]],
            ['ORD-10020', 'lukas.brenner@example.test', 'delivered', 61, [['AN-WDY-001', 1]]],
            ['ORD-10021', 'maya.okafor@example.test', 'delivered', 58, [['AN-AMB-003', 1]]],
            ['ORD-10022', 'iris.van.dijk@example.test', 'delivered', 55, [['AN-WDY-002', 1], ['AN-FLR-001', 2]]],
            ['ORD-10023', 'sofia.lindqvist@example.test', 'delivered', 52, [['AN-AMB-001', 1], ['AN-FRS-002', 1], ['AN-GRM-001', 1]]],
            ['ORD-10024', 'arthur.bennett@example.test', 'delivered', 49, [['AN-GRM-002', 2], ['AN-FRS-003', 2]]],
            ['ORD-10025', 'lukas.brenner@example.test', 'delivered', 46, [['AN-AMB-002', 2], ['AN-FLR-002', 2]]],
            ['ORD-10026', 'clara.fontaine@example.test', 'delivered', 43, [['AN-WDY-001', 1]]],
            ['ORD-10027', 'sebastian.wolf@example.test', 'delivered', 40, [['AN-AMB-003', 1]]],
            ['ORD-10028', 'amelie.laurent@example.test', 'delivered', 37, [['AN-AMB-001', 1]]],
            ['ORD-10029', 'sebastian.wolf@example.test', 'delivered', 34, [['AN-FLR-001', 1]]],
            ['ORD-10030', 'matteo.bianchi@example.test', 'cancelled', 31, [['AN-FLR-002', 1], ['AN-AMB-001', 1], ['AN-FRS-002', 1]]],
            ['ORD-10031', 'daniel.kovac@example.test', 'delivered', 28, [['AN-AMB-002', 1], ['AN-FLR-003', 1], ['AN-FRS-003', 1]]],
            ['ORD-10032', 'theo.marchetti@example.test', 'delivered', 25, [['AN-FLR-002', 2], ['AN-GRM-002', 2]]],
            ['ORD-10033', 'clara.fontaine@example.test', 'delivered', 22, [['AN-AMB-003', 1], ['AN-WDY-002', 1]]],
            ['ORD-10034', 'amelie.laurent@example.test', 'shipped', 19, [['AN-GRM-001', 1]]],
            ['ORD-10035', 'matteo.bianchi@example.test', 'shipped', 16, [['AN-AMB-003', 1], ['AN-GRM-001', 1]]],
            ['ORD-10036', 'amelie.laurent@example.test', 'shipped', 13, [['AN-FRS-001', 1]]],
            ['ORD-10037', 'elena.petrova@example.test', 'processing', 10, [['AN-GRM-001', 1]]],
            ['ORD-10038', 'amelie.laurent@example.test', 'processing', 7, [['AN-WDY-002', 1], ['AN-FRS-003', 1], ['AN-FLR-002', 1]]],
            ['ORD-10039', 'hugo.lefevre@example.test', 'pending', 4, [['AN-GRM-002', 1], ['AN-WDY-001', 1]]],
            ['ORD-10040', 'oliver.hart@example.test', 'pending', 1, [['AN-GRM-002', 2], ['AN-WDY-001', 1], ['AN-GRM-001', 1]]],
        ];
    }
}
