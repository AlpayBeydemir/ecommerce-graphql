<?php

namespace App\GraphQL\Mutations;

use App\Models\Product;
use GraphQL\Error\Error;

class CreateProduct
{
    public function __invoke($rootValue, array $args)
    {
        // Create product
        $product = Product::create([
            'name' => $args['name'],
            'description' => $args['description'] ?? null,
            'sku' => $args['sku'],
            'price' => $args['price'],
            'stock_quantity' => $args['stock_quantity'],
            'brand' => $args['brand'] ?? null,
            'image_url' => $args['image_url'] ?? null,
            'is_active' => $args['is_active'] ?? true,
        ]);

        // TODO: Dispatch job to index product in Elasticsearch
        // dispatch(new IndexProduct($product));

        return $product;
    }
}
