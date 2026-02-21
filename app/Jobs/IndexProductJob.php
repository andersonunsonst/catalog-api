<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\ElasticSearchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class IndexProductJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Product $product
    ) {}

    public function handle(ElasticSearchService $elasticSearch): void
    {
        try {
            $elasticSearch->indexProduct($this->product);
            Log::info("Product {$this->product->id} indexed via queue");
        } catch (\Exception $e) {
            Log::error("Failed to index product {$this->product->id}: " . $e->getMessage());
            throw $e; // Retry automático
        }
    }
}
