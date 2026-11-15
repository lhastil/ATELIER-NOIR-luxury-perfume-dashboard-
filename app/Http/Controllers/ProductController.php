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
        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $this->storeImage($request);

            if ($imagePath === null) {
                return $this->imageUploadFailed();
            }
        }

        try {
            $product = Product::create([...$data, 'image_path' => $imagePath]);
        } catch (Throwable $exception) {
            // The product was not saved, so its new file is not needed.
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
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
        $oldPath = $product->image_path;
        $newPath = $oldPath;

        if ($request->hasFile('image')) {
            $newPath = $this->storeImage($request);

            if ($newPath === null) {
                return $this->imageUploadFailed();
            }
        } elseif ($request->boolean('remove_image')) {
            $newPath = null;
        }

        try {
            // updateOrFail() throws if the product could not be saved.
            $product->updateOrFail([...$data, 'image_path' => $newPath]);
        } catch (Throwable $exception) {
            // The product keeps its previous image, so only the new upload is removed.
            if ($newPath && $newPath !== $oldPath) {
                Storage::disk('public')->delete($newPath);
            }

            throw $exception;
        }

        // The old file is deleted only once the product points to its new image (or none).
        if ($oldPath && $oldPath !== $newPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()->route('products.index')->with('status', "“{$product->name}” was updated.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        // Deleting a file that is already missing does nothing.
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        return redirect()->route('products.index')->with('status', "“{$product->name}” was deleted.");
    }

    /**
     * Stores the uploaded image under a random name, e.g. products/abc123.jpg.
     * The extension comes from the file's content, not the name it was sent with.
     * Returns null if the file could not be written.
     */
    private function storeImage(ProductRequest $request): ?string
    {
        return $request->file('image')->store('products', 'public') ?: null;
    }

    private function imageUploadFailed(): RedirectResponse
    {
        return back()->withInput()->withErrors(['image' => 'The image could not be uploaded. Please try again.']);
    }
}
