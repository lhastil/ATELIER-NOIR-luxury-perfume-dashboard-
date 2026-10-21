<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        // A malformed query such as ?search[]=x counts as no search.
        $search = is_string($request->query('search')) ? trim($request->query('search')) : '';
        $categoryId = $request->integer('category');
        $status = in_array($request->query('status'), ['active', 'hidden'], true) ? $request->query('status') : null;

        $products = Product::query()
            // Only the image's id is needed for its address, not its bytes.
            ->with(['category', 'image:id,product_id'])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%");
            }))
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->when($status, fn ($query) => $query->where('is_active', $status === 'active'))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        return view('products.index', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(),
            'filters' => ['search' => $search, 'category' => $categoryId ?: null, 'status' => $status],
        ]);
    }

    public function create(): View
    {
        return view('products.create', [
            'product' => new Product(['is_active' => true]),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'remove_image']);

        // The product and its image are saved together, or not at all.
        $product = DB::transaction(function () use ($request, $data) {
            $product = Product::create($data);

            if ($request->hasFile('image')) {
                $product->image()->create(ProductImage::attributesFor($request->file('image')));
            }

            return $product;
        });

        return redirect()->route('products.index')->with('status', "“{$product->name}” was added to the catalog.");
    }

    public function edit(Product $product): View
    {
        $product->load('image:id,product_id');

        return view('products.edit', [
            'product' => $product,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'remove_image']);

        // If any part fails, the product keeps its previous details and image.
        DB::transaction(function () use ($request, $product, $data) {
            $product->update($data);

            if ($request->hasFile('image')) {
                // A new record, so the replaced image gets a new id and a new address.
                $product->image()->delete();
                $product->image()->create(ProductImage::attributesFor($request->file('image')));
            } elseif ($request->boolean('remove_image')) {
                $product->image()->delete();
            }
        });

        return redirect()->route('products.index')->with('status', "“{$product->name}” was updated.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        // The database deletes the product's image together with it.
        $product->delete();

        return redirect()->route('products.index')->with('status', "“{$product->name}” was deleted.");
    }

    /**
     * Serves a product's stored image. The content type is the one detected from
     * the file when it was uploaded; the request cannot choose what is returned.
     */
    public function image(Product $product): Response
    {
        $image = $product->image()->firstOrFail();

        return response($image->data, 200, [
            'Content-Type' => $image->mime_type,
            'Content-Length' => (string) strlen($image->data),
            // A replaced image gets a new id, so a new address (see Product::imageUrl).
            'Cache-Control' => 'private, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
