<?php

namespace App\Services;

use App\Models\Product;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Log;

class ElasticSearchService
{
    private Client $client;

    private string $index = 'products';

    public function __construct()
    {
        $this->client = ClientBuilder::create()
            ->setHosts([
                config('services.elasticsearch.host', 'http://elasticsearch:9200'),
            ])
            ->build();
    }

    /**
     * Create the products index if it doesn't exist.
     */
    public function createIndex(): void
    {
        try {
            if ($this->client->indices()->exists(['index' => $this->index])->asBool()) {
                return;
            }

            $this->client->indices()->create([
                'index' => $this->index,
                'body' => [
                    'mappings' => [
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'sku' => ['type' => 'keyword'],
                            'name' => ['type' => 'text'],
                            'description' => ['type' => 'text'],
                            'price' => ['type' => 'float'],
                            'category' => ['type' => 'keyword'],
                            'status' => ['type' => 'keyword'],
                            'image_url' => ['type' => 'keyword'],
                            'created_at' => ['type' => 'date'],
                            'updated_at' => ['type' => 'date'],
                        ],
                    ],
                ],
            ]);

            Log::info('ElasticSearch index created successfully');
        } catch (\Exception $e) {
            Log::error('Failed to create ElasticSearch index: '.$e->getMessage());
        }
    }

    /**
     * Index a product.
     */
    public function indexProduct(Product $product): void
    {
        try {
            $this->client->index([
                'index' => $this->index,
                'id' => $product->id,
                'body' => [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'description' => $product->description,
                    'price' => (float) $product->price,
                    'category' => strtolower($product->category),
                    'status' => $product->status,
                    'image_url' => $product->image_url,
                    'created_at' => $product->created_at?->toIso8601String(),
                    'updated_at' => $product->updated_at?->toIso8601String(),
                ],
            ]);

            Log::info("Product {$product->id} indexed successfully");
        } catch (\Exception $e) {
            Log::error("Failed to index product {$product->id}: ".$e->getMessage());
        }
    }

    /**
     * Update a product in the index.
     */
    public function updateProduct(Product $product): void
    {
        $this->indexProduct($product);
    }

    /**
     * Delete a product from the index.
     */
    public function deleteProduct(int $productId): void
    {
        try {
            $this->client->delete([
                'index' => $this->index,
                'id' => $productId,
            ]);

            Log::info("Product {$productId} deleted from index");
        } catch (\Exception $e) {
            Log::error("Failed to delete product {$productId} from index: ".$e->getMessage());
        }
    }

    /**
     * Search products.
     */
    public function search(array $params): array
    {
        $must = [];
        $filter = [];

        // Text search
        if (! empty($params['q'])) {
            $must[] = [
                'multi_match' => [
                    'query' => $params['q'],
                    'fields' => ['name^2', 'description'],
                ],
            ];
        }

        // Category filter
        if (! empty($params['category'])) {
            $filter[] = ['term' => ['category' => strtolower($params['category'])]];
        }

        // Status filter
        if (! empty($params['status'])) {
            $filter[] = ['term' => ['status' => $params['status']]];
        }

        // Price range
        if (! empty($params['min_price']) || ! empty($params['max_price'])) {
            $range = [];
            if (! empty($params['min_price'])) {
                $range['gte'] = (float) $params['min_price'];
            }
            if (! empty($params['max_price'])) {
                $range['lte'] = (float) $params['max_price'];
            }
            $filter[] = ['range' => ['price' => $range]];
        }

        // Build query
        $body = [
            'query' => [
                'bool' => [],
            ],
        ];

        if (! empty($must)) {
            $body['query']['bool']['must'] = $must;
        }

        if (! empty($filter)) {
            $body['query']['bool']['filter'] = $filter;
        }

        // If no conditions, match all
        if (empty($must) && empty($filter)) {
            $body['query'] = ['match_all' => (object) []];
        }

        // Sorting
        $sortBy = $params['sort'] ?? 'created_at';
        $sortOrder = $params['order'] ?? 'desc';
        $body['sort'] = [
            $sortBy => ['order' => $sortOrder],
        ];

        // Pagination
        $page = $params['page'] ?? 1;
        $perPage = $params['per_page'] ?? 15;
        $body['from'] = ($page - 1) * $perPage;
        $body['size'] = $perPage;

        try {
            $response = $this->client->search([
                'index' => $this->index,
                'body' => $body,
            ]);

            $hits = $response['hits'];
            $products = array_map(function ($hit) {
                return $hit['_source'];
            }, $hits['hits']);

            return [
                'data' => $products,
                'total' => $hits['total']['value'],
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($hits['total']['value'] / $perPage),
            ];
        } catch (\Exception $e) {
            Log::error('ElasticSearch search failed: '.$e->getMessage());

            return [
                'data' => [],
                'total' => 0,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => 0,
            ];
        }
    }

    /**
     * Bulk index all products.
     */
    public function bulkIndexProducts(): void
    {
        $products = Product::all();

        foreach ($products as $product) {
            $this->indexProduct($product);
        }

        Log::info("Bulk indexed {$products->count()} products");
    }
}
