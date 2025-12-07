<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\ElasticsearchService;
use Illuminate\Support\Facades\Log;

class ProductObserver
{
    protected ElasticsearchService $elasticsearchService;

    public function __construct(ElasticsearchService $elasticsearchService)
    {
        $this->elasticsearchService = $elasticsearchService;
    }

    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        if ($product->is_active) {
            try {
                $this->elasticsearchService->indexProduct($product);
                Log::info('Product indexed in Elasticsearch', ['product_id' => $product->id]);
            } catch (\Exception $e) {
                Log::error('Failed to index product in Elasticsearch', [
                    'product_id' => $product->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        try {
            if ($product->is_active) {
                $this->elasticsearchService->indexProduct($product);
                Log::info('Product updated in Elasticsearch', ['product_id' => $product->id]);
            } else {
                $this->elasticsearchService->deleteProduct($product->id);
                Log::info('Inactive product removed from Elasticsearch', ['product_id' => $product->id]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update product in Elasticsearch', [
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        try {
            $this->elasticsearchService->deleteProduct($product->id);
            Log::info('Product deleted from Elasticsearch', ['product_id' => $product->id]);
        } catch (\Exception $e) {
            Log::error('Failed to delete product from Elasticsearch', [
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the Product "restored" event (soft delete).
     */
    public function restored(Product $product): void
    {
        if ($product->is_active) {
            try {
                $this->elasticsearchService->indexProduct($product);
                Log::info('Restored product indexed in Elasticsearch', ['product_id' => $product->id]);
            } catch (\Exception $e) {
                Log::error('Failed to index restored product in Elasticsearch', [
                    'product_id' => $product->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
