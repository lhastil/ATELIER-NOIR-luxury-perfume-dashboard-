<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The migration that moves product images into the database. It changes the
 * schema, so each test gets a freshly migrated database instead of a transaction.
 */
class ProductImageMigrationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_existing_images_are_copied_and_their_files_kept(): void
    {
        $disk = Storage::fake('public');
        $jpeg = UploadedFile::fake()->image('a.jpg')->getContent();
        $png = UploadedFile::fake()->image('b.png')->getContent();
        $disk->put('products/a.jpg', $jpeg);
        $disk->put('products/b.png', $png);
        $disk->put('products/notes.jpg', 'not an image');

        $this->migration()->down();
        $withJpeg = $this->productWithPath('products/a.jpg');
        $withPng = $this->productWithPath('products/b.png');
        $missing = $this->productWithPath('products/gone.jpg');
        $notAnImage = $this->productWithPath('products/notes.jpg');
        $withoutPath = $this->productWithPath(null);

        $this->migration()->up();

        $this->assertSame(5, Product::count());
        $this->assertFalse(Schema::hasColumn('products', 'image_path'));
        $this->assertEqualsCanonicalizing([$withJpeg->id, $withPng->id], DB::table('product_images')->pluck('product_id')->all());
        $this->assertSame(['image/jpeg', $jpeg], $this->storedImage($withJpeg));
        $this->assertSame(['image/png', $png], $this->storedImage($withPng));
        $this->assertNull($this->storedImage($missing));
        $this->assertNull($this->storedImage($notAnImage));
        $this->assertNull($this->storedImage($withoutPath));
        $disk->assertExists(['products/a.jpg', 'products/b.png', 'products/notes.jpg']);
    }

    public function test_an_interrupted_run_is_rebuilt_with_the_complete_table(): void
    {
        $disk = Storage::fake('public');
        $jpeg = UploadedFile::fake()->image('a.jpg')->getContent();
        $png = UploadedFile::fake()->image('b.png')->getContent();
        $disk->put('products/a.jpg', $jpeg);
        $disk->put('products/b.png', $png);

        // A run that stopped after creating the table but before its constraints,
        // the wider column and the copy: no foreign key, no unique index, and on
        // MySQL a 64 KB BLOB column. One row was already copied.
        $this->migration()->down();
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('mime_type', 50);
            $table->binary('data');
            $table->timestamps();
        });
        $copiedBefore = $this->productWithPath('products/a.jpg');
        $notYetCopied = $this->productWithPath('products/b.png');
        DB::table('product_images')->insert(['product_id' => $copiedBefore->id, 'mime_type' => 'image/jpeg', 'data' => 'partial copy', 'created_at' => now(), 'updated_at' => now()]);

        $this->migration()->up();

        $this->assertCompleteImageTable();
        $this->assertSame(['image/jpeg', $jpeg], $this->storedImage($copiedBefore));
        $this->assertSame(['image/png', $png], $this->storedImage($notYetCopied));
        $this->assertSame(2, DB::table('product_images')->count());
        $this->assertFalse(Schema::hasColumn('products', 'image_path'));

        // Once completed, running it again changes nothing.
        $this->migration()->up();
        $this->assertSame(['image/jpeg', $jpeg], $this->storedImage($copiedBefore));
        $this->assertSame(2, DB::table('product_images')->count());
    }

    public function test_unsafe_paths_and_symbolic_links_are_skipped(): void
    {
        $disk = Storage::fake('public');
        $jpeg = UploadedFile::fake()->image('a.jpg')->getContent();
        $disk->put('products/a.jpg', $jpeg);
        $disk->put('elsewhere/b.jpg', $jpeg);

        // A real image outside the disk, linked from inside it.
        $outside = tempnam(sys_get_temp_dir(), 'image');
        file_put_contents($outside, $jpeg);
        symlink($outside, $disk->path('products/link.jpg'));

        $this->migration()->down();
        $valid = $this->productWithPath('products/a.jpg');
        $skipped = array_map(fn (string $path) => $this->productWithPath($path), [
            '../a.jpg',
            'products/../../outside.jpg',
            'products/..',
            '/products/a.jpg',
            'elsewhere/b.jpg',
            'products/link.jpg',
        ]);

        try {
            $this->migration()->up();
        } finally {
            unlink($outside);
        }

        $this->assertSame(7, Product::count());
        $this->assertFalse(Schema::hasColumn('products', 'image_path'));
        $this->assertSame([$valid->id], DB::table('product_images')->pluck('product_id')->all());
        $this->assertSame(['image/jpeg', $jpeg], $this->storedImage($valid));
        foreach ($skipped as $product) {
            $this->assertNull($this->storedImage($product));
        }
    }

    public function test_rolling_back_restores_the_column_but_not_the_images(): void
    {
        $product = Product::factory()->create();
        DB::table('product_images')->insert(['product_id' => $product->id, 'mime_type' => 'image/png', 'data' => 'bytes', 'created_at' => now(), 'updated_at' => now()]);

        $this->migration()->down();

        $this->assertFalse(Schema::hasTable('product_images'));
        $this->assertTrue(Schema::hasColumn('products', 'image_path'));
        $this->assertNull(DB::table('products')->where('id', $product->id)->value('image_path'));
        $this->assertSame(1, Product::count());
    }

    /**
     * The table has its foreign key (deleting with the product), its unique index
     * and, on MySQL, the MEDIUMBLOB column that holds images over 64 KB.
     */
    private function assertCompleteImageTable(): void
    {
        $foreignKeys = Schema::getForeignKeys('product_images');
        $this->assertCount(1, $foreignKeys);
        $this->assertSame(['product_id'], $foreignKeys[0]['columns']);
        $this->assertSame('products', $foreignKeys[0]['foreign_table']);
        $this->assertSame('cascade', strtolower($foreignKeys[0]['on_delete']));

        $this->assertTrue(collect(Schema::getIndexes('product_images'))
            ->contains(fn (array $index) => $index['unique'] && $index['columns'] === ['product_id']));

        if (DB::getDriverName() === 'mysql') {
            $this->assertSame('mediumblob', strtolower(Schema::getColumnType('product_images', 'data', true)));
        }
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_10_21_000006_create_product_images_table.php');
    }

    private function productWithPath(?string $path): Product
    {
        $product = Product::factory()->create();
        DB::table('products')->where('id', $product->id)->update(['image_path' => $path]);

        return $product;
    }

    /**
     * @return array{string, string}|null
     */
    private function storedImage(Product $product): ?array
    {
        $image = DB::table('product_images')->where('product_id', $product->id)->first();

        return $image ? [$image->mime_type, $image->data] : null;
    }
}
