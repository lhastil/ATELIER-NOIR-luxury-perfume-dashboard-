<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        // A malformed query such as ?search[]=x counts as no search.
        $search = is_string($request->query('search')) ? trim($request->query('search')) : '';
        $categoryId = $request->integer('category');
        $status = in_array($request->query('status'), ['active', 'hidden'], true) ? $request->query('status') : null;

        $products = Product::query()
            ->with('category')
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

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store(Product::IMAGE_DIRECTORY, 'public');
        }

        try {
            $product = Product::create($data);
        } catch (Throwable $exception) {
            // The product was not saved, so the file just uploaded would have no owner.
            if (isset($data['image_path'])) {
                Storage::disk('public')->delete($data['image_path']);
            }

            throw $exception;
        }

        return redirect()->route('products.index')->with('status', "“{$product->name}” was added to the catalog.");
    }

    public function edit(Product $product): View
    {
        return view('products.edit', [
            'product' => $product,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'remove_image']);
        $previousImage = $product->image_path;
        $newImage = null;

        if ($request->hasFile('image')) {
            $newImage = $data['image_path'] = $request->file('image')->store(Product::IMAGE_DIRECTORY, 'public');
        } elseif ($request->boolean('remove_image')) {
            $data['image_path'] = null;
        }

        try {
            $product->update($data);
        } catch (Throwable $exception) {
            // Only the file uploaded by this request is removed; the current image stays.
            if ($newImage) {
                Storage::disk('public')->delete($newImage);
            }

            throw $exception;
        }

        // Only this product's previous file is deleted, and only once the new state is saved.
        if ($previousImage && $previousImage !== $product->image_path) {
            Storage::disk('public')->delete($previousImage);
        }

        return redirect()->route('products.index')->with('status', "“{$product->name}” was updated.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        return redirect()->route('products.index')->with('status', "“{$product->name}” was deleted.");
    }
}
