<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Requests\UploadImageRequest;
use App\Services\ImageUploadService;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService,
        private ImageUploadService $imageUploadService
    ) {}

    /**
     * Display a listing of products.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['category', 'status', 'min_price', 'max_price', 'search', 'sort', 'order']);
        $perPage = min((int) $request->input('per_page', 15), 100);

        $products = $this->productService->getPaginatedProducts($filters, $perPage);

        return response()->json($products);
    }

    /**
     * Store a newly created product.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->createProduct($request->validated());

        return response()->json([
            'message' => 'Product created successfully',
            'data' => $product,
        ], 201);
    }

    /**
     * Display the specified product.
     */
    public function show(int $id): JsonResponse
    {
        $product = $this->productService->getProductById($id);

        if (!$product) {
            abort(404, 'Product not found');
        }

        return response()->json([
            'data' => $product,
        ]);
    }

    /**
     * Update the specified product.
     */
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $product = $this->productService->updateProduct($id, $request->validated());

        return response()->json([
            'message' => 'Product updated successfully',
            'data' => $product,
        ]);
    }

    /**
     * Remove the specified product.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->productService->deleteProduct($id);

        return response()->json([
            'message' => 'Product deleted successfully',
        ]);
    }

    /**
     * Upload product image.
     */
    public function uploadImage(UploadImageRequest $request, int $id): JsonResponse
    {
        $product = $this->productService->getProductById($id);

        if (!$product) {
            abort(404, 'Product not found');
        }

        $imageUrl = $this->imageUploadService->uploadProductImage(
            $request->file('image'),
            $id
        );

        $this->productService->updateProduct($id, ['image_url' => $imageUrl]);

        return response()->json([
            'message' => 'Image uploaded successfully',
            'data' => [
                'image_url' => $imageUrl,
            ],
        ]);
    }
}
