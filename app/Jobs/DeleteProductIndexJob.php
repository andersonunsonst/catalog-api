<?php

namespace App\Jobs;

use App\Services\ElasticSearchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DeleteProductIndexJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $productId
    ) {}

    public function handle(ElasticSearchService $elasticSearch): void
    {
        try {
            $elasticSearch->deleteProduct($this->productId);
            Log::info("Product {$this->productId} deleted from index via queue");
        } catch (\Exception $e) {
            Log::error("Failed to delete product {$this->productId}: ".$e->getMessage());
            throw $e;
        }
    }
}
