<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

/**
 * The migration that moves product images from the database to files. It
 * changes the schema, so each test gets a freshly migrated database.
 */
class ProductImagePathMigrationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_it_adds_the_image_path_column_and_drops_the_empty_image_table(): void
    {
        // The test database has already run every migration, including this one.
        $this->assertTrue(Schema::hasColumn('products', 'image_path'));
        $this->assertFalse(Schema::hasTable('product_images'));
    }

    public function test_it_stops_without_changes_when_images_are_still_in_the_database(): void
    {
        $this->migration()->down();
        $product = Product::factory()->create();
        DB::table('product_images')->insert(['product_id' => $product->id, 'mime_type' => 'image/png', 'data' => 'bytes', 'created_at' => now(), 'updated_at' => now()]);

        try {
            $this->migration()->up();
            $this->fail('The migration should have stopped.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('product_images table still contains images', $exception->getMessage());
        }

        $this->assertSame(1, DB::table('product_images')->count());
        $this->assertFalse(Schema::hasColumn('products', 'image_path'));
    }

    public function test_rolling_back_restores_the_empty_image_table_and_removes_the_column(): void
    {
        $this->migration()->down();

        $this->assertFalse(Schema::hasColumn('products', 'image_path'));
        $this->assertSame(0, DB::table('product_images')->count());

        $foreignKeys = Schema::getForeignKeys('product_images');
        $this->assertCount(1, $foreignKeys);
        $this->assertSame(['product_id'], $foreignKeys[0]['columns']);
        $this->assertSame('cascade', strtolower($foreignKeys[0]['on_delete']));
        $this->assertTrue(collect(Schema::getIndexes('product_images'))
            ->contains(fn (array $index) => $index['unique'] && $index['columns'] === ['product_id']));
    }

    public function test_rolling_back_stops_when_products_still_have_image_files(): void
    {
        $product = Product::factory()->create(['image_path' => 'products/bottle.jpg']);

        try {
            $this->migration()->down();
            $this->fail('The rollback should have stopped.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Some products still have image files', $exception->getMessage());
        }

        $this->assertTrue(Schema::hasColumn('products', 'image_path'));
        $this->assertFalse(Schema::hasTable('product_images'));

        // Lets DatabaseMigrations roll the test database back after this test.
        $product->delete();
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_11_11_000002_move_product_images_to_files.php');
    }
}
