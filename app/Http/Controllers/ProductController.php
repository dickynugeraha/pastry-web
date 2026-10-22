<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\CartService;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function home(Request $request, CartService $carts): View
    {
        $featuredProducts = Product::query()
            ->available()
            ->with(['category:id,name,slug', 'primaryImage'])
            ->latest()
            ->limit(4)
            ->get();
        $cartQuantities = $carts->quantitiesForProducts($request->user(), $featuredProducts->modelKeys());

        return view('welcome', compact('featuredProducts', 'cartQuantities'));
    }

    public function index(Request $request, ProductService $products, CartService $carts): JsonResponse|View
    {
        $filters = $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'available_only' => ['sometimes', 'boolean'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $products = $products->paginate($filters);

        if (! $request->expectsJson()) {
            $cartQuantities = $carts->quantitiesForProducts($request->user(), $products->getCollection()->modelKeys());

            return view('shop.products.index', [
                'products' => $products,
                'categories' => Category::query()->orderBy('name')->get(),
                'filters' => $filters,
                'cartQuantities' => $cartQuantities,
            ]);
        }

        return response()->json(['data' => $products]);
    }

    public function show(Request $request, Product $product, CartService $carts): JsonResponse|View
    {
        abort_unless($product->is_active, 404);

        $product->load(['category', 'primaryImage']);

        if (! $request->expectsJson()) {
            $cartQuantities = $carts->quantitiesForProducts($request->user(), [$product->id]);

            return view('shop.products.show', compact('product', 'cartQuantities'));
        }

        return response()->json(['data' => $product]);
    }
}
