<?php

namespace App\GraphQL\Queries;

use App\Services\ElasticsearchService;

class SearchProducts
{
    protected $elasticsearchService;

    public function __construct(ElasticsearchService $elasticsearchService)
    {
        $this->elasticsearchService = $elasticsearchService;
    }

    public function __invoke($rootValue, array $args)
    {
        $query = $args['query'];
        $filters = [];

        if (isset($args['brand'])) {
            $filters['brand'] = $args['brand'];
        }

        if (isset($args['minPrice'])) {
            $filters['minPrice'] = $args['minPrice'];
        }

        if (isset($args['maxPrice'])) {
            $filters['maxPrice'] = $args['maxPrice'];
        }

        if (isset($args['inStock'])) {
            $filters['inStock'] = $args['inStock'];
        }

        $page = $args['page'] ?? 1;
        $limit = $args['limit'] ?? 20;

        return $this->elasticsearchService->searchProducts($query, $filters, $page, $limit);
    }
}
