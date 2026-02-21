<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\ElasticSearchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class UpdateProductIndexJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Product $product
    ) {}

    public function handle(ElasticSearchService $elasticSearch): void
    {
        try {
            $elasticSearch->updateProduct($this->product);
            Log::info("Product {$this->product->id} updated in index via queue");
        } catch (\Exception $e) {
            Log::error("Failed to update product {$this->product->id}: " . $e->getMessage());
            throw $e;
        }
    }
}