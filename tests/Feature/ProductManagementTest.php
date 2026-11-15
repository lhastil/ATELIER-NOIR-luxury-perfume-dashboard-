<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SampleProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every test uses a temporary public disk, so no real files are written.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_guests_cannot_reach_product_pages(): void
    {
        $product = Product::factory()->create();

        $this->get(route('products.index'))->assertRedirect(route('login'));
        $this->get(route('products.create'))->assertRedirect(route('login'));
        $this->post(route('products.store'), $this->validData())->assertRedirect(route('login'));
        $this->put(route('products.update', $product), $this->validData())->assertRedirect(route('login'));
        $this->delete(route('products.destroy', $product))->assertRedirect(route('login'));

        $this->assertSame(1, Product::count());
    }

    public function test_the_index_lists_products_with_price_in_euros(): void
    {
        Product::factory()->create(['name' => 'Rose Nocturne', 'price' => 128, 'stock' => 0]);

        $this->signIn()->get(route('products.index'))
            ->assertOk()
            ->assertSee('Rose Nocturne')
            ->assertSee('€128.00')
            ->assertSee('Out of stock');
    }

    public function test_search_and_filters_narrow_the_list(): void
    {
        $floral = Category::factory()->create(['name' => 'Floral']);
        $woody = Category::factory()->create(['name' => 'Woody']);
        Product::factory()->for($floral)->create(['name' => 'Rose Nocturne', 'sku' => 'AN-FLR-001']);
        Product::factory()->for($woody)->create(['name' => 'Cèdre Ombré', 'sku' => 'AN-WDY-001']);
        Product::factory()->for($woody)->hidden()->create(['name' => 'Forêt Sauvage', 'sku' => 'AN-WDY-002']);

        $this->signIn();

        $this->get(route('products.index', ['search' => 'wdy-001']))
            ->assertSee('Cèdre Ombré')->assertDontSee('Rose Nocturne')->assertDontSee('Forêt Sauvage');

        $this->get(route('products.index', ['category' => $floral->id]))
            ->assertSee('Rose Nocturne')->assertDontSee('Cèdre Ombré');

        $this->get(route('products.index', ['status' => 'hidden']))
            ->assertSee('Forêt Sauvage')->assertDontSee('Cèdre Ombré');

        $this->get(route('products.index', ['search' => 'nothing like this']))
            ->assertSee('No matching products');

        $this->get('/products?search[]=wdy')->assertOk()->assertSee('Rose Nocturne')->assertSee('Cèdre Ombré');
    }

    public function test_pagination_keeps_the_filters(): void
    {
        Product::factory()->hidden()->count(12)->create();

        $this->signIn()->get(route('products.index', ['status' => 'hidden']))
            ->assertOk()
            ->assertSee('status=hidden&amp;page=2', false);

        $this->get(route('products.index', ['status' => 'hidden', 'page' => 9]))
            ->assertSee('This page is empty');
    }

    public function test_an_admin_can_add_a_product_with_an_image(): void
    {
        $data = $this->validData(['sku' => 'an-flr-009', 'image' => UploadedFile::fake()->image('bottle.jpg', 600, 600)]);

        $this->signIn()->post(route('products.store'), $data)
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('status');

        $product = Product::sole();
        $this->assertSame('AN-FLR-009', $product->sku);
        $this->assertSame('84.50', $product->price);
        $this->assertStringStartsWith('products/', $product->image_path);
        Storage::disk('public')->assertExists($product->image_path);
        $this->assertSame($data['image']->getContent(), Storage::disk('public')->get($product->image_path));
    }

    public function test_an_admin_can_add_a_product_without_an_image(): void
    {
        $this->signIn()->post(route('products.store'), $this->validData())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('products.index'));

        $product = Product::sole();
        $this->assertNull($product->image_path);
        $this->assertNull($product->image_url);
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_the_file_extension_comes_from_the_file_content_not_the_browser(): void
    {
        // A real JPEG sent with a misleading name and browser-reported type.
        $jpeg = UploadedFile::fake()->image('source.jpg');
        $upload = new UploadedFile($jpeg->getPathname(), 'photo.png', 'text/html', null, true);

        $this->signIn()->post(route('products.store'), $this->validData(['image' => $upload]))
            ->assertRedirect(route('products.index'));

        $this->assertStringEndsWith('.jpg', Product::sole()->image_path);
    }

    public function test_invalid_input_is_rejected_with_messages(): void
    {
        Product::factory()->create(['sku' => 'AN-TAKEN-1']);

        $this->signIn()
            ->from(route('products.create'))
            ->post(route('products.store'), $this->validData([
                'name' => '',
                'sku' => 'an-taken-1',
                'price' => '-5',
                'stock' => '2.5',
                'concentration' => 'Body Mist',
                'image' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
            ]))
            ->assertRedirect(route('products.create'))
            ->assertSessionHasErrors(['name', 'sku', 'price', 'stock', 'concentration', 'image']);

        $this->assertSame(1, Product::count());
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_images_over_2_mb_are_rejected(): void
    {
        $this->signIn()
            ->from(route('products.create'))
            ->post(route('products.store'), $this->validData(['image' => UploadedFile::fake()->image('bottle.jpg')->size(2049)]))
            ->assertRedirect(route('products.create'))
            ->assertSessionHasErrors('image');

        $this->assertSame(0, Product::count());
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_an_admin_can_update_a_product_and_keep_its_sku(): void
    {
        $product = Product::factory()->create(['sku' => 'AN-FLR-001', 'stock' => 5]);

        $this->signIn()->get(route('products.edit', $product))->assertOk()->assertSee('AN-FLR-001');

        $this->put(route('products.update', $product), $this->validData(['sku' => 'AN-FLR-001', 'stock' => 12, 'is_active' => '0']))
            ->assertRedirect(route('products.index'));

        $product->refresh();
        $this->assertSame(12, $product->stock);
        $this->assertFalse($product->is_active);
    }

    public function test_an_update_cannot_take_another_products_sku(): void
    {
        Product::factory()->create(['sku' => 'AN-FLR-001']);
        $product = Product::factory()->create(['sku' => 'AN-FLR-002']);

        $this->signIn()->put(route('products.update', $product), $this->validData(['sku' => 'an-flr-001']))
            ->assertSessionHasErrors('sku');

        $this->assertSame('AN-FLR-002', $product->refresh()->sku);
    }

    public function test_a_product_can_be_added_without_a_category(): void
    {
        $this->signIn()->get(route('products.create'))
            ->assertOk()
            ->assertSee('<option value="">No category</option>', false);

        $this->post(route('products.store'), $this->validData(['category_id' => '']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('products.index'));

        $this->assertNull(Product::sole()->category_id);

        // The list shows the product instead of failing on the missing category.
        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('No category');
    }

    public function test_a_products_category_can_be_removed(): void
    {
        $product = Product::factory()->create();

        $this->signIn()->put(route('products.update', $product), $this->validData(['category_id' => '']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('products.index'));

        $this->assertNull($product->refresh()->category_id);

        $this->get(route('products.edit', $product))
            ->assertOk()
            ->assertSee('<option value="">No category</option>', false);
    }

    public function test_a_category_that_does_not_exist_is_rejected(): void
    {
        $this->signIn()
            ->from(route('products.create'))
            ->post(route('products.store'), $this->validData(['category_id' => 999999]))
            ->assertRedirect(route('products.create'))
            ->assertSessionHasErrors('category_id');

        $this->assertSame(0, Product::count());
    }

    public function test_an_update_without_a_new_image_keeps_the_current_one(): void
    {
        $product = $this->productWithImage('current.jpg');
        $path = $product->image_path;

        $this->signIn()->get(route('products.edit', $product))
            ->assertOk()
            ->assertSee('src="'.e($product->image_url).'"', false);

        $this->put(route('products.update', $product), $this->validData(['name' => 'Renamed']))
            ->assertRedirect(route('products.index'));

        $product->refresh();
        $this->assertSame('Renamed', $product->name);
        $this->assertSame($path, $product->image_path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_a_new_image_replaces_the_old_file(): void
    {
        $product = $this->productWithImage('old.jpg');
        $other = $this->productWithImage('other.jpg', ['sku' => 'AN-OTHER-1']);
        $oldPath = $product->image_path;
        $new = UploadedFile::fake()->image('new.png');

        $this->signIn()->put(route('products.update', $product), $this->validData(['image' => $new]))
            ->assertRedirect(route('products.index'));

        $newPath = $product->refresh()->image_path;
        $this->assertNotSame($oldPath, $newPath);
        $this->assertSame($new->getContent(), Storage::disk('public')->get($newPath));
        Storage::disk('public')->assertMissing($oldPath);
        // Other products' images are not touched.
        Storage::disk('public')->assertExists($other->image_path);
    }

    public function test_a_failed_create_saves_neither_the_product_nor_the_image(): void
    {
        Product::creating(fn () => throw new RuntimeException('Database unavailable'));

        $this->signIn()->post(route('products.store'), $this->validData(['image' => UploadedFile::fake()->image('bottle.jpg')]))
            ->assertServerError();

        $this->assertSame(0, Product::count());
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_a_failed_update_keeps_the_current_image(): void
    {
        $product = $this->productWithImage('current.jpg');
        $path = $product->image_path;
        Product::updating(fn () => throw new RuntimeException('Database unavailable'));

        $this->signIn()->put(route('products.update', $product), $this->validData(['name' => 'Renamed', 'image' => UploadedFile::fake()->image('new.png')]))
            ->assertServerError();

        $product->refresh();
        $this->assertNotSame('Renamed', $product->name);
        $this->assertSame($path, $product->image_path);
        // The old file is kept and the new upload is cleaned up.
        $this->assertSame([$path], Storage::disk('public')->allFiles());
    }

    public function test_the_current_image_can_be_removed(): void
    {
        $product = $this->productWithImage();
        $path = $product->image_path;

        $this->signIn()->put(route('products.update', $product), $this->validData(['remove_image' => '1']))
            ->assertRedirect(route('products.index'));

        $this->assertNull($product->refresh()->image_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_deleting_a_product_removes_it_and_its_image(): void
    {
        $product = $this->productWithImage();
        $other = $this->productWithImage('other.jpg', ['sku' => 'AN-OTHER-1']);

        $this->signIn()->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('status');

        $this->assertModelMissing($product);
        $this->assertModelExists($other);
        Storage::disk('public')->assertMissing($product->image_path);
        Storage::disk('public')->assertExists($other->image_path);
    }

    public function test_deleting_a_product_whose_file_is_already_missing_still_works(): void
    {
        $product = $this->productWithImage();
        Storage::disk('public')->delete($product->image_path);

        $this->signIn()->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'));

        $this->assertModelMissing($product);
    }

    public function test_the_list_links_public_image_files_and_shows_a_placeholder_otherwise(): void
    {
        $product = $this->productWithImage('bottle.jpg', ['name' => 'Rose Nocturne']);
        Product::factory()->create(['name' => 'Ambre Royal', 'sku' => 'AN-NOIMG-1']);

        $url = Storage::disk('public')->url($product->image_path);
        $this->assertSame($url, $product->image_url);

        $this->signIn()->get(route('products.index'))
            ->assertSee('<img class="product-thumb" src="'.e($url).'"', false)
            ->assertSee('product-thumb--empty', false);
    }

    public function test_delete_buttons_ask_for_confirmation(): void
    {
        $product = Product::factory()->create(['name' => 'Rose Nocturne']);

        $this->signIn()->get(route('products.index'))
            ->assertSee('data-confirm="Delete “Rose Nocturne”? This cannot be undone."', false);
    }

    public function test_sample_data_seeds_once_and_keeps_edits(): void
    {
        $this->seed(SampleProductSeeder::class);
        Product::where('sku', 'AN-FLR-001')->update(['price' => '150.00']);
        $this->seed([CategorySeeder::class, SampleProductSeeder::class]);

        $this->assertSame(CategorySeeder::NAMES, Category::orderBy('id')->pluck('name')->all());
        $this->assertSame(15, Product::count());
        $this->assertSame(15, Product::distinct()->count('sku'));
        $this->assertSame(5, Product::distinct()->count('category_id'));
        $this->assertSame('150.00', Product::where('sku', 'AN-FLR-001')->value('price'));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function productWithImage(string $file = 'bottle.jpg', array $attributes = []): Product
    {
        $path = UploadedFile::fake()->image($file)->store('products', 'public');

        return Product::factory()->create([...$attributes, 'image_path' => $path]);
    }

    private function signIn(): static
    {
        return $this->actingAs(User::factory()->create());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Jardin d’Iris',
            'sku' => 'AN-FLR-002',
            'brand' => 'Maison Ivrelle',
            'category_id' => Category::firstOrCreate(['name' => 'Floral'])->id,
            'concentration' => 'Eau de Parfum',
            'volume_ml' => 100,
            'price' => '84.50',
            'stock' => 20,
            'is_active' => '1',
            'description' => 'Powdery iris with pear.',
        ], $overrides);
    }
}
