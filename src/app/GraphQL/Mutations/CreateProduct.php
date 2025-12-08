<?php

namespace App\GraphQL\Mutations;

use App\Models\Product;

class CreateProduct
{
    public function __invoke($rootValue, array $args)
    {
        return Product::create([
            'name' => $args['name'],
            'description' => $args['description'] ?? null,
            'sku' => $args['sku'],
            'category' => 'general',
            'price' => $args['price'],
            'stock_quantity' => $args['stock_quantity'],
            'brand' => $args['brand'] ?? null,
            'image_url' => $args['image_url'] ?? null,
            'is_active' => $args['is_active'] ?? true,
        ]);
    }
}
