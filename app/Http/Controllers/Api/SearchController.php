<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchProductRequest;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}

    /**
     * Search products using ElasticSearch.
     */
    public function searchProducts(SearchProductRequest $request): JsonResponse
    {
        try {
            $params = $request->validated();
            $results = $this->productService->searchProducts($params);

            return response()->json($results, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Search failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
