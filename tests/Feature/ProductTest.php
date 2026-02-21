<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test creating a product successfully.
     */
    public function test_can_create_product(): void
    {
        $productData = [
            'sku' => 'TEST-SKU-001',
            'name' => 'Test Product',
            'description' => 'This is a test product description',
            'price' => 99.99,
            'category' => 'Electronics',
            'status' => 'active',
        ];

        $response = $this->postJson('/api/products', $productData);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Product created successfully',
            ])
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'sku',
                    'name',
                    'description',
                    'price',
                    'category',
                    'status',
                    'created_at',
                    'updated_at',
                ]
            ]);

        $this->assertDatabaseHas('products', [
            'sku' => 'TEST-SKU-001',
            'name' => 'Test Product',
        ]);
    }

    /**
     * Test creating a product with validation errors.
     */
    public function test_create_product_validation_fails(): void
    {
        $productData = [
            'sku' => 'TEST',
            'name' => 'AB', // Too short
            'price' => -10, // Negative price
        ];

        $response = $this->postJson('/api/products', $productData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'price', 'category']);
    }

    /**
     * Test SKU uniqueness validation.
     */
    public function test_cannot_create_product_with_duplicate_sku(): void
    {
        // Create first product
        $this->postJson('/api/products', [
            'sku' => 'DUPLICATE-SKU',
            'name' => 'First Product',
            'price' => 99.99,
            'category' => 'Test',
        ])->assertStatus(201);

        // Try to create second product with same SKU
        $response = $this->postJson('/api/products', [
            'sku' => 'DUPLICATE-SKU',
            'name' => 'Second Product',
            'price' => 199.99,
            'category' => 'Test',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'SKU already exists',
            ]);
    }

    /**
     * Test retrieving a product by ID.
     */
    public function test_can_get_product_by_id(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                ]
            ]);
    }

    /**
     * Test product not found returns 404.
     */
    public function test_show_nonexistent_product_returns_404(): void
    {
        $response = $this->getJson('/api/products/99999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Resource not found',
            ]);
    }

    /**
     * Test cache is working.
     */
    public function test_product_is_cached_after_first_request(): void
    {
        $product = Product::factory()->create();

        // First request (no cache)
        $this->getJson("/api/products/{$product->id}")
            ->assertStatus(200);

        // Verify cache exists
        $cacheKey = "product.{$product->id}";
        $this->assertTrue(Cache::has($cacheKey));

        // Second request (from cache)
        $cachedProduct = Cache::get($cacheKey);
        $this->assertEquals($product->id, $cachedProduct->id);
    }

    /**
     * Test cache is invalidated on update.
     */
    public function test_cache_is_cleared_when_product_is_updated(): void
    {
        $product = Product::factory()->create();
        $cacheKey = "product.{$product->id}";

        // Cache the product
        $this->getJson("/api/products/{$product->id}");
        $this->assertTrue(Cache::has($cacheKey));

        // Update product
        $this->putJson("/api/products/{$product->id}", [
            'name' => 'Updated Name',
        ])->assertStatus(200);

        // Cache should be cleared
        $this->assertFalse(Cache::has($cacheKey));
    }

    /**
     * Test updating a product.
     */
    public function test_can_update_product(): void
    {
        $product = Product::factory()->create();

        $updateData = [
            'name' => 'Updated Product Name',
            'price' => 149.99,
        ];

        $response = $this->putJson("/api/products/{$product->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Product updated successfully',
                'data' => [
                    'name' => 'Updated Product Name',
                    'price' => '149.99',
                ]
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product Name',
        ]);
    }

    /**
     * Test deleting a product.
     */
    public function test_can_delete_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Product deleted successfully'
            ]);

        $this->assertSoftDeleted('products', [
            'id' => $product->id,
        ]);
    }

    /**
     * Test listing products with pagination.
     */
    public function test_can_list_products_with_pagination(): void
    {
        Product::factory()->count(20)->create();

        $response = $this->getJson('/api/products?per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'per_page',
                'total',
            ]);
    }

    /**
     * Test filtering products by category.
     */
    public function test_can_filter_products_by_category(): void
    {
        Product::factory()->create(['category' => 'Electronics']);
        Product::factory()->create(['category' => 'Books']);

        $response = $this->getJson('/api/products?category=Electronics');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        foreach ($data as $product) {
            $this->assertEquals('Electronics', $product['category']);
        }
    }

    /**
     * Test filtering products by status.
     */
    public function test_can_filter_products_by_status(): void
    {
        Product::factory()->active()->count(5)->create();
        Product::factory()->inactive()->count(3)->create();

        $response = $this->getJson('/api/products?status=active');

        $response->assertStatus(200);

        $data = $response->json('data');
        foreach ($data as $product) {
            $this->assertEquals('active', $product['status']);
        }
    }

    /**
     * Test filtering products by price range.
     */
    public function test_can_filter_products_by_price_range(): void
    {
        Product::factory()->create(['price' => 50.00]);
        Product::factory()->create(['price' => 150.00]);
        Product::factory()->create(['price' => 250.00]);

        $response = $this->getJson('/api/products?min_price=100&max_price=200');

        $response->assertStatus(200);

        $data = $response->json('data');
        foreach ($data as $product) {
            $this->assertGreaterThanOrEqual(100, $product['price']);
            $this->assertLessThanOrEqual(200, $product['price']);
        }
    }

    /**
     * Test validation errors return 422.
     */
    public function test_create_product_with_invalid_data_returns_422(): void
    {
        $response = $this->postJson('/api/products', [
            'sku' => '', // Required
            'name' => 'Test',
            'price' => -10, // Must be positive
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
            ])
            ->assertJsonStructure([
                'errors' => ['sku', 'price'],
            ]);
    }
}
