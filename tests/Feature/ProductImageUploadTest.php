<?php

namespace Tests\Feature;

use App\Models\Product;
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

        // Fake S3 storage (não precisa de credenciais reais)
        Storage::fake('s3');
    }

    public function test_can_upload_product_image(): void
    {
        // Criar produto
        $product = Product::factory()->create();

        // Criar imagem fake
        $file = UploadedFile::fake()->image('product.jpg', 800, 600);

        // Upload
        $response = $this->postJson("/api/products/{$product->id}/image", [
            'image' => $file,
        ]);

        // Verificar resposta
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'sku',
                    'name',
                    'image_url',
                ],
            ]);

        // Verificar que arquivo foi "enviado" para S3
        $product->refresh();
        $this->assertNotNull($product->image_url);

        // Verificar que arquivo existe no storage fake
        Storage::disk('s3')->assertExists(
            str_replace(Storage::disk('s3')->url(''), '', $product->image_url)
        );
    }

    public function test_upload_validates_file_type(): void
    {
        $product = Product::factory()->create();

        // Tentar enviar arquivo não-imagem
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

        // Arquivo muito grande (6MB)
        $file = UploadedFile::fake()->image('huge.jpg')->size(6000);

        $response = $this->postJson("/api/products/{$product->id}/image", [
            'image' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_upload_returns_404_for_nonexistent_product(): void
    {
        $file = UploadedFile::fake()->image('product.jpg');

        $response = $this->postJson('/api/products/99999/image', [
            'image' => $file,
        ]);

        $response->assertStatus(404);
    }
}
