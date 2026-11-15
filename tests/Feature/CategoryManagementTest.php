<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_reach_category_pages(): void
    {
        $category = Category::factory()->create(['name' => 'Floral']);

        $this->get(route('categories.index'))->assertRedirect(route('login'));
        $this->get(route('categories.create'))->assertRedirect(route('login'));
        $this->post(route('categories.store'), ['name' => 'Woody'])->assertRedirect(route('login'));
        $this->get(route('categories.edit', $category))->assertRedirect(route('login'));
        $this->put(route('categories.update', $category), ['name' => 'Changed'])->assertRedirect(route('login'));
        $this->delete(route('categories.destroy', $category))->assertRedirect(route('login'));

        $this->assertSame(['Floral'], Category::pluck('name')->all());
    }

    public function test_the_list_shows_categories_by_name_with_their_product_counts(): void
    {
        $woody = Category::factory()->create(['name' => 'Woody']);
        Category::factory()->create(['name' => 'Amber']);
        Product::factory()->count(2)->for($woody)->create();

        $this->signIn()->get(route('categories.index'))
            ->assertOk()
            ->assertSeeInOrder(['Amber', 'Woody'])
            ->assertSee('<a class="table-link" href="'.e(route('products.index', ['category' => $woody->id])).'">2</a>', false)
            ->assertSee('data-confirm="Delete the category &quot;Woody&quot;? This cannot be undone."', false);
    }

    public function test_an_admin_can_add_a_category(): void
    {
        $this->signIn()->get(route('categories.create'))->assertOk();

        $this->post(route('categories.store'), ['name' => '  Citrus  '])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('status', 'Category "Citrus" was added.');

        $this->assertSame(['Citrus'], Category::pluck('name')->all());
    }

    public function test_an_admin_can_rename_a_category_and_keep_its_products(): void
    {
        $category = Category::factory()->create(['name' => 'Floral']);
        $product = Product::factory()->for($category)->create();

        $this->signIn()->get(route('categories.edit', $category))->assertOk()->assertSee('value="Floral"', false);

        $this->put(route('categories.update', $category), ['name' => 'White Floral'])
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('status', 'Category "White Floral" was updated.');

        $this->assertSame('White Floral', $category->refresh()->name);
        $this->assertSame($category->id, $product->refresh()->category_id);
    }

    public function test_a_category_may_keep_its_own_name_when_saved(): void
    {
        $category = Category::factory()->create(['name' => 'Floral']);

        $this->signIn()->put(route('categories.update', $category), ['name' => 'Floral'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('categories.index'));
    }

    public function test_invalid_names_are_rejected(): void
    {
        Category::factory()->create(['name' => 'Floral']);

        $this->signIn();

        foreach (['' => 'required', str_repeat('a', 61) => 'too long', 'Floral' => 'taken'] as $name => $reason) {
            $this->from(route('categories.create'))
                ->post(route('categories.store'), ['name' => $name])
                ->assertRedirect(route('categories.create'))
                ->assertSessionHasErrors('name');
        }

        $this->assertSame(1, Category::count());
    }

    public function test_a_category_cannot_be_renamed_to_another_categorys_name(): void
    {
        Category::factory()->create(['name' => 'Floral']);
        $woody = Category::factory()->create(['name' => 'Woody']);

        $this->signIn()->from(route('categories.edit', $woody))
            ->put(route('categories.update', $woody), ['name' => 'Floral'])
            ->assertRedirect(route('categories.edit', $woody))
            ->assertSessionHasErrors('name');

        $this->assertSame('Woody', $woody->refresh()->name);
    }

    public function test_an_empty_category_can_be_deleted(): void
    {
        $category = Category::factory()->create(['name' => 'Floral']);
        $other = Category::factory()->create(['name' => 'Woody']);

        $this->signIn()->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('status', 'Category "Floral" was deleted.');

        $this->assertModelMissing($category);
        $this->assertModelExists($other);
    }

    public function test_a_category_with_products_is_not_deleted(): void
    {
        $category = Category::factory()->create(['name' => 'Floral']);
        $products = Product::factory()->count(2)->for($category)->create();

        $this->signIn()->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'))
            ->assertSessionHasErrors(['category' => 'Category "Floral" still has 2 products. Move those products to another category, or set them to "No category", before deleting it.']);

        $this->assertModelExists($category);
        foreach ($products as $product) {
            $this->assertSame($category->id, $product->refresh()->category_id);
        }

        // Following the redirect, the message is shown on the list.
        $this->followingRedirects()
            ->delete(route('categories.destroy', $category))
            ->assertOk()
            ->assertSee('<div class="form-alert" role="alert">', false)
            ->assertSee('Category &quot;Floral&quot; still has 2 products.', false);

        $this->assertModelExists($category);
    }

    public function test_unknown_categories_return_not_found(): void
    {
        $this->signIn();

        $this->get(route('categories.edit', 999999))->assertNotFound();
        $this->put(route('categories.update', 999999), ['name' => 'Floral'])->assertNotFound();
        $this->delete(route('categories.destroy', 999999))->assertNotFound();
    }

    public function test_the_sidebar_links_to_categories_and_marks_the_section_as_current(): void
    {
        $html = $this->signIn()->get(route('categories.create'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('#<a href="'.preg_quote(route('categories.index'), '#').'" class="nav-item"\s+aria-current="page"#', $html);
    }

    private function signIn(): static
    {
        return $this->actingAs(User::factory()->create());
    }
}
