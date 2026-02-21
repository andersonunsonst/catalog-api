<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Services\ElasticSearchService;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductService $productService;

    private ProductRepository $repository;

    private ElasticSearchService $elasticSearch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(ProductRepository::class);
        $this->elasticSearch = $this->app->make(ElasticSearchService::class);
        $this->productService = new ProductService($this->repository, $this->elasticSearch);
    }

    /**
     * Test creating a product with valid data.
     */
    public function test_create_product_with_valid_data(): void
    {
        $data = [
            'sku' => 'TEST-001',
            'name' => 'Test Product',
            'description' => 'Test description',
            'price' => 99.99,
            'category' => 'Electronics',
            'status' => 'active',
        ];

        $product = $this->productService->createProduct($data);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals('TEST-001', $product->sku);
        $this->assertEquals('Test Product', $product->name);
        $this->assertDatabaseHas('products', ['sku' => 'TEST-001']);
    }

    /**
     * Test creating a product with duplicate SKU throws exception.
     */
    public function test_create_product_with_duplicate_sku_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SKU already exists');

        Product::factory()->create(['sku' => 'DUPLICATE']);

        $this->productService->createProduct([
            'sku' => 'DUPLICATE',
            'name' => 'Test Product',
            'price' => 99.99,
            'category' => 'Electronics',
        ]);
    }

    /**
     * Test updating a product.
     */
    public function test_update_product(): void
    {
        $product = Product::factory()->create();

        $updatedProduct = $this->productService->updateProduct($product->id, [
            'name' => 'Updated Name',
            'price' => 149.99,
        ]);

        $this->assertEquals('Updated Name', $updatedProduct->name);
        $this->assertEquals('149.99', $updatedProduct->price);
    }

    /**
     * Test updating non-existent product throws exception.
     */
    public function test_update_nonexistent_product_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Product not found');

        $this->productService->updateProduct(99999, ['name' => 'Test']);
    }

    /**
     * Test deleting a product.
     */
    public function test_delete_product(): void
    {
        $product = Product::factory()->create();

        $result = $this->productService->deleteProduct($product->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    /**
     * Test deleting non-existent product throws exception.
     */
    public function test_delete_nonexistent_product_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Product not found');

        $this->productService->deleteProduct(99999);
    }

    /**
     * Test getting product by ID.
     */
    public function test_get_product_by_id(): void
    {
        $product = Product::factory()->create();

        $foundProduct = $this->productService->getProductById($product->id);

        $this->assertInstanceOf(Product::class, $foundProduct);
        $this->assertEquals($product->id, $foundProduct->id);
    }

    /**
     * Test getting non-existent product returns null.
     */
    public function test_get_nonexistent_product_returns_null(): void
    {
        $product = $this->productService->getProductById(99999);

        $this->assertNull($product);
    }
}
