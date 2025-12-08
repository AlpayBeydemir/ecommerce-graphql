<?php

namespace App\GraphQL\Mutations;

use App\Models\Product;
use GraphQL\Error\Error;

class UpdateProduct
{
    public function __invoke($rootValue, array $args)
    {
        $product = Product::find($args['id']);

        if (!$product) {
            throw new Error('Product not found');
        }

        $updateData = [];

        if (isset($args['name'])) {
            $updateData['name'] = $args['name'];
        }

        if (isset($args['description'])) {
            $updateData['description'] = $args['description'];
        }

        if (isset($args['sku'])) {
            $updateData['sku'] = $args['sku'];
        }

        $updateData['category'] = 'general';

        if (isset($args['price'])) {
            $updateData['price'] = $args['price'];
        }

        if (isset($args['stock_quantity'])) {
            $updateData['stock_quantity'] = $args['stock_quantity'];
        }

        if (isset($args['brand'])) {
            $updateData['brand'] = $args['brand'];
        }

        if (isset($args['image_url'])) {
            $updateData['image_url'] = $args['image_url'];
        }

        if (isset($args['is_active'])) {
            $updateData['is_active'] = $args['is_active'];
        }

        $product->update($updateData);

        return $product->fresh();
    }
}
