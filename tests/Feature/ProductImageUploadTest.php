<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\ImageUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_can_upload_product_image(): void
    {
        $this->mock(ImageUploadService::class, function ($mock) {
            $mock->shouldReceive('uploadProductImage')
                ->once()
                ->andReturn('http://localhost/storage/products/1/test.jpg');
        });

        $product = Product::factory()->create();

        $response = $this->postJson("/api/products/{$product->id}/image", [
            'image' => UploadedFile::fake()->create('product.jpg', 100, 'image/jpeg'),
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'image_url',
                ],
            ]);

        $this->assertNotNull($product->fresh()->image_url);
    }

    public function test_upload_validates_file_type(): void
    {
        $product = Product::factory()->create();

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->postJson("/api/products/{$product->id}/image", [
            'image' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_upload_validates_file_size(): void
    {
        $product = Product::factory()->create();

        $file = UploadedFile::fake()->create('huge.jpg', 6000, 'image/jpeg');

        $response = $this->postJson("/api/products/{$product->id}/image", [
            'image' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_upload_returns_404_for_nonexistent_product(): void
    {
        $file = UploadedFile::fake()->create('product.jpg', 100, 'image/jpeg');

        $response = $this->postJson('/api/products/99999/image', [
            'image' => $file,
        ]);

        $response->assertStatus(404);
    }
}
