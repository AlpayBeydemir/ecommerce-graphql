<?php

namespace App\GraphQL\Mutations;

use App\Models\Product;
use GraphQL\Error\Error;

class DeleteProduct
{
    public function __invoke($rootValue, array $args)
    {
        $product = Product::find($args['id']);

        if (!$product) {
            throw new Error('Product not found');
        }

        // Soft delete
        $product->delete();

        // TODO: Dispatch job to remove product from Elasticsearch
        // dispatch(new RemoveProductIndex($product));

        return [
            'message' => 'Product deleted successfully',
            'success' => true,
        ];
    }
}
