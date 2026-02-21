<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class ProductService
{
    public function __construct(
        private ProductRepository $repository,
        private ElasticSearchService $elasticSearch
    ) {}

    /**
     * Get paginated products with caching.
     */
    public function getPaginatedProducts(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $page = $filters['page'] ?? 1;

        // Don't cache high page numbers
        if ($page > 50) {
            return $this->repository->paginate($filters, $perPage);
        }

        $cacheKey = $this->getCacheKey('products.list', $filters, $perPage);

        return Cache::remember($cacheKey, 120, function () use ($filters, $perPage) {
            return $this->repository->paginate($filters, $perPage);
        });
    }

    /**
     * Get a product by ID with caching.
     */
    public function getProductById(int $id): ?Product
    {
        $cacheKey = "product.{$id}";

        return Cache::remember($cacheKey, 120, function () use ($id) {
            return $this->repository->findById($id);
        });
    }

    /**
     * Create a new product.
     */
    public function createProduct(array $data): Product
    {
        // Validate SKU uniqueness
        if ($this->repository->findBySku($data['sku'])) {
            throw new \InvalidArgumentException('SKU already exists');
        }

        $product = $this->repository->create($data);
        $this->clearListCache();

        \Log::info('Product created', [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'price' => $product->price,
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
        ]);

        return $product;
    }

    /**
     * Update a product.
     */
    public function updateProduct(int $id, array $data): Product
    {
        $product = $this->getProductById($id);

        if (!$product) {
            throw new \InvalidArgumentException('Product not found');
        }

        // Validate SKU uniqueness if changing
        if (isset($data['sku']) && $data['sku'] !== $product->sku) {
            if ($this->repository->findBySku($data['sku'])) {
                throw new \InvalidArgumentException('SKU already exists');
            }
        }

        $oldData = $product->only(['name', 'price', 'status', 'category']);
        $product = $this->repository->update($product, $data);
        $this->clearProductCache($id);

        \Log::info('Product updated', [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'changes' => array_keys(array_diff_assoc($data, $oldData)),
            'user_id' => auth()->id(),
        ]);

        return $product;
    }

    /**
     * Delete a product.
     */
    public function deleteProduct(int $id): bool
    {
        $product = $this->getProductById($id);

        if (!$product) {
            throw new \InvalidArgumentException('Product not found');
        }

        $result = $this->repository->delete($product);
        $this->clearProductCache($id);

        \Log::info('Product deleted (soft delete)', [
            'product_id' => $id,
            'sku' => $product->sku,
            'name' => $product->name,
            'user_id' => auth()->id(),
        ]);

        return $result;
    }

    /**
     * Search products using ElasticSearch with caching.
     */
    public function searchProducts(array $params): array
    {
        $page = $params['page'] ?? 1;
        $perPage = $params['per_page'] ?? 15;

        // Don't cache high page numbers
        if ($page > 50) {
            return $this->elasticSearch->search($params);
        }

        $cacheKey = $this->getCacheKey('products.search', $params, $perPage);

        return Cache::remember($cacheKey, 120, function () use ($params) {
            return $this->elasticSearch->search($params);
        });
    }

    /**
     * Generate cache key from parameters.
     */
    private function getCacheKey(string $prefix, array $params, ?int $perPage = null): string
    {
        ksort($params);
        $key = $prefix . '.' . md5(json_encode($params));

        if ($perPage) {
            $key .= ".{$perPage}";
        }

        return $key;
    }

    /**
     * Clear all list caches.
     */
    private function clearListCache(): void
    {
        // In production, you might want to use cache tags for better invalidation
        Cache::flush();
    }

    /**
     * Clear cache for a specific product.
     */
    private function clearProductCache(int $id): void
    {
        Cache::forget("product.{$id}");
        $this->clearListCache();
    }
}

