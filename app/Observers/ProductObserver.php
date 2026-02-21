<?php

namespace App\Observers;

use App\Models\Product;
use App\Jobs\IndexProductJob;
use App\Jobs\UpdateProductIndexJob;
use App\Jobs\DeleteProductIndexJob;

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
