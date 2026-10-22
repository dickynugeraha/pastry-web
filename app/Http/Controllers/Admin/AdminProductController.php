<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminProductRequest;
use App\Http\Requests\UpdateAdminProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminProductController extends Controller
{
    public function index(Request $request, ProductService $products): JsonResponse|View
    {
        $filters = $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $products = $products->paginate($filters, true);

        if (! $request->expectsJson()) {
            return view('admin.products.index', compact('products', 'filters'));
        }

        return response()->json(['data' => $products]);
    }

    public function create(): View
    {
        return view('admin.products.create', [
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function show(Product $product): JsonResponse|View
    {
        $product->load(['category', 'images']);

        if (! request()->expectsJson()) {
            return view('admin.products.show', compact('product'));
        }

        return response()->json(['data' => $product]);
    }

    public function store(StoreAdminProductRequest $request, ProductService $products): JsonResponse|RedirectResponse
    {
        $product = $products->create($request->validated());
        $product = $products->syncImages($product, $request->file('images', []));

        if (! $request->expectsJson()) {
            return redirect()->route('admin.products.show', $product)->with('success', 'Product created.');
        }

        return response()->json([
            'message' => 'Product created.',
            'data' => $product,
        ], 201);
    }

    public function edit(Product $product): View
    {
        return view('admin.products.edit', [
            'product' => $product->load('images'),
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function update(
        UpdateAdminProductRequest $request,
        Product $product,
        ProductService $products
    ): JsonResponse|RedirectResponse {
        $validated = $request->validated();
        $updatedProduct = $products->update($product, $validated);
        $updatedProduct = $products->syncImages(
            $updatedProduct,
            $request->file('images', []),
            $validated['remove_image_ids'] ?? [],
            isset($validated['primary_image_id']) ? (int) $validated['primary_image_id'] : null,
        );

        if (! $request->expectsJson()) {
            return redirect()->route('admin.products.show', $updatedProduct)->with('success', 'Product updated.');
        }

        return response()->json([
            'message' => 'Product updated.',
            'data' => $updatedProduct,
        ]);
    }

    public function destroy(Product $product, ProductService $products): JsonResponse|RedirectResponse
    {
        $products->delete($product);

        if (! request()->expectsJson()) {
            return redirect()->route('admin.products.index')->with('success', 'Product deleted.');
        }

        return response()->json(['message' => 'Product deleted.']);
    }
}
