<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ProductService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, bool $includeInactive = false): LengthAwarePaginator
    {
        $query = Product::query()->with(['category:id,name,slug', 'primaryImage']);

        if (! $includeInactive) {
            $query->active();
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (($filters['available_only'] ?? false) && ! $includeInactive) {
            $query->available();
        }

        return $query
            ->orderBy('name')
            ->orderBy('id')
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Product
    {
        $attributes = Arr::only($attributes, [
            'category_id',
            'name',
            'slug',
            'description',
            'price',
            'stock',
            'is_active',
        ]);

        return DB::transaction(function () use ($attributes): Product {
            $attributes['slug'] = $this->uniqueSlug($attributes['slug'] ?? $attributes['name']);

            return Product::create($attributes)->load(['category', 'images']);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Product $product, array $attributes): Product
    {
        $attributes = Arr::only($attributes, [
            'category_id',
            'name',
            'slug',
            'description',
            'price',
            'stock',
            'is_active',
        ]);

        return DB::transaction(function () use ($product, $attributes): Product {
            if (array_key_exists('slug', $attributes) || array_key_exists('name', $attributes)) {
                $attributes['slug'] = $this->uniqueSlug(
                    $attributes['slug'] ?? $product->slug,
                    $product->id
                );
            }

            $product->update($attributes);

            return $product->refresh()->load(['category', 'images']);
        });
    }

    public function delete(Product $product): void
    {
        $imagePaths = $product->images()->pluck('path')->all();
        $product->delete();
        Storage::disk('public')->delete($imagePaths);
    }

    /**
     * @param  array<int, UploadedFile>  $uploadedImages
     * @param  array<int, int|string>  $removeImageIds
     */
    public function syncImages(
        Product $product,
        array $uploadedImages = [],
        array $removeImageIds = [],
        ?int $primaryImageId = null
    ): Product {
        $storedPaths = [];

        try {
            foreach ($uploadedImages as $image) {
                if ($image instanceof UploadedFile) {
                    $storedPaths[] = $image->store("products/{$product->id}", 'public');
                }
            }

            $pathsToDelete = DB::transaction(function () use (
                $product,
                $removeImageIds,
                $primaryImageId,
                $storedPaths
            ): array {
                $images = $product->images()->lockForUpdate()->get();
                $removeImageIds = array_map('intval', $removeImageIds);
                $imagesToRemove = $images->whereIn('id', $removeImageIds);
                $pathsToDelete = $imagesToRemove->pluck('path')->all();

                if ($imagesToRemove->isNotEmpty()) {
                    $product->images()->whereIn('id', $imagesToRemove->pluck('id'))->delete();
                }

                $remainingImages = $images->reject(
                    fn (ProductImage $image): bool => in_array($image->id, $removeImageIds, true)
                );
                $nextSortOrder = ((int) $remainingImages->max('sort_order')) + 1;
                $hasPrimaryImage = $remainingImages->contains('is_primary', true);

                foreach ($storedPaths as $index => $path) {
                    $product->images()->create([
                        'path' => $path,
                        'is_primary' => ! $hasPrimaryImage && $index === 0,
                        'sort_order' => $nextSortOrder + $index,
                    ]);
                }

                if ($primaryImageId !== null && $product->images()->whereKey($primaryImageId)->exists()) {
                    $product->images()->update(['is_primary' => false]);
                    $product->images()->whereKey($primaryImageId)->update(['is_primary' => true]);
                } elseif (! $product->images()->where('is_primary', true)->exists()) {
                    $product->images()
                        ->orderBy('sort_order')
                        ->orderBy('id')
                        ->first()
                        ?->update(['is_primary' => true]);
                }

                return $pathsToDelete;
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($storedPaths);

            throw $exception;
        }

        Storage::disk('public')->delete($pathsToDelete);

        return $product->refresh()->load(['category', 'images']);
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'product';
        $slug = $base;
        $suffix = 2;

        while (Product::query()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
