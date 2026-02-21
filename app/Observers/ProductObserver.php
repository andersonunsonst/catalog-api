<?php

namespace App\Observers;

use App\Jobs\DeleteProductIndexJob;
use App\Jobs\IndexProductJob;
use App\Jobs\UpdateProductIndexJob;
use App\Models\Product;

class ProductObserver
{
    public function created(Product $product): void
    {
        IndexProductJob::dispatch($product);
    }

    public function updated(Product $product): void
    {
        UpdateProductIndexJob::dispatch($product);
    }

    public function deleted(Product $product): void
    {
        DeleteProductIndexJob::dispatch($product->id);
    }

    public function restored(Product $product): void
    {
        IndexProductJob::dispatch($product);
    }

    public function forceDeleted(Product $product): void
    {
        DeleteProductIndexJob::dispatch($product->id);
    }
}
