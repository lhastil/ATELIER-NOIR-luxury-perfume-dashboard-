<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()
            ->withCount('products')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(10);

        return view('categories.index', ['categories' => $categories]);
    }

    public function create(): View
    {
        return view('categories.create', ['category' => new Category]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = Category::create($request->validated());

        return redirect()->route('categories.index')->with('status', "Category \"{$category->name}\" was added.");
    }

    public function edit(Category $category): View
    {
        return view('categories.edit', ['category' => $category]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('categories.index')->with('status', "Category \"{$category->name}\" was updated.");
    }

    /**
     * A category that still has products is not deleted: its products are never
     * changed behind the administrator's back. The database enforces the same rule.
     */
    public function destroy(Category $category): RedirectResponse
    {
        $productCount = $category->products()->count();

        if ($productCount > 0) {
            return redirect()->route('categories.index')->withErrors([
                'category' => "Category \"{$category->name}\" still has {$productCount} ".str('product')->plural($productCount)
                    .'. Move those products to another category, or set them to "No category", before deleting it.',
            ]);
        }

        $category->delete();

        return redirect()->route('categories.index')->with('status', "Category \"{$category->name}\" was deleted.");
    }
}
