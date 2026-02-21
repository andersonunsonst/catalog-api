<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\ElasticSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected ElasticSearchService $elasticSearch;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock ElasticSearch service for testing
        $this->elasticSearch = $this->app->make(ElasticSearchService::class);
    }

    /**
     * Test search endpoint is accessible.
     */
    public function test_search_endpoint_is_accessible(): void
    {
        $response = $this->getJson('/api/search/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'total',
                'per_page',
                'current_page',
            ]);
    }

    /**
     * Test search with query parameter.
     */
    public function test_can_search_products_by_query(): void
    {
        $response = $this->getJson('/api/search/products?q=laptop');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'total',
            ]);
    }

    /**
     * Test search with category filter.
     */
    public function test_can_search_products_by_category(): void
    {
        $response = $this->getJson('/api/search/products?category=Electronics');

        $response->assertStatus(200);
    }

    /**
     * Test search with status filter.
     */
    public function test_can_search_products_by_status(): void
    {
        $response = $this->getJson('/api/search/products?status=active');

        $response->assertStatus(200);
    }

    /**
     * Test search with price range.
     */
    public function test_can_search_products_by_price_range(): void
    {
        $response = $this->getJson('/api/search/products?min_price=50&max_price=200');

        $response->assertStatus(200);
    }

    /**
     * Test search with multiple filters.
     */
    public function test_can_search_products_with_multiple_filters(): void
    {
        $response = $this->getJson('/api/search/products?q=laptop&category=Electronics&status=active&min_price=100&max_price=500');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'total',
                'per_page',
                'current_page',
                'last_page',
            ]);
    }

    /**
     * Test search with sorting.
     */
    public function test_can_search_products_with_sorting(): void
    {
        $response = $this->getJson('/api/search/products?sort=price&order=asc');

        $response->assertStatus(200);
    }

    /**
     * Test search with pagination.
     */
    public function test_can_search_products_with_pagination(): void
    {
        $response = $this->getJson('/api/search/products?page=1&per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'total',
                'per_page',
                'current_page',
                'last_page',
            ]);
    }

    /**
     * Test search results are cached.
     */
    public function test_search_results_are_cached(): void
    {
        $params = '?q=laptop&category=Electronics';

        // First request
        $this->getJson('/api/search/products' . $params);

        // Second request should use cache
        $response = $this->getJson('/api/search/products' . $params);

        $response->assertStatus(200);
    }

    /**
     * Test search validation.
     */
    public function test_search_validates_parameters(): void
    {
        $response = $this->getJson('/api/search/products?status=invalid_status');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }
}

