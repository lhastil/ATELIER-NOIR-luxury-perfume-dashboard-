<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

/**
 * The migration that makes a product's category optional. It changes the
 * schema, so each test gets a freshly migrated database.
 */
class ProductCategoryMigrationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_the_category_becomes_optional_and_existing_categories_are_kept(): void
    {
        $this->migration()->down();
        $this->assertFalse($this->categoryIsNullable());
        $product = Product::factory()->create();

        $this->migration()->up();

        $this->assertTrue($this->categoryIsNullable());
        $this->assertSame($product->category_id, $product->fresh()->category_id);

        // A category still cannot be deleted while products use it.
        $foreignKey = collect(Schema::getForeignKeys('products'))->firstWhere('columns', ['category_id']);
        $this->assertSame('categories', $foreignKey['foreign_table']);
        $this->assertSame('restrict', strtolower($foreignKey['on_delete']));
    }

    public function test_rolling_back_stops_while_products_have_no_category(): void
    {
        $product = Product::factory()->create(['category_id' => null]);

        try {
            $this->migration()->down();
            $this->fail('The rollback should have stopped.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Some products have no category', $exception->getMessage());
        }

        $this->assertTrue($this->categoryIsNullable());
        $this->assertModelExists($product);

        // Lets DatabaseMigrations roll the test database back after this test.
        $product->delete();
    }

    private function categoryIsNullable(): bool
    {
        return collect(Schema::getColumns('products'))->firstWhere('name', 'category_id')['nullable'];
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_11_11_000003_make_product_category_optional.php');
    }
}
