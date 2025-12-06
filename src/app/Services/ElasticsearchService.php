<?php

namespace App\Services;

use App\Models\Product;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Client;

class ElasticsearchService
{
    protected Client $client;
    protected string $indexName;

    public function __construct()
    {
        $this->client = ClientBuilder::create()
            ->setHosts([config('services.elasticsearch.host', 'http://elasticsearch:9200')])
            ->setElasticMetaHeader(false) // Disable version check
            ->build();

        $this->indexName = config('services.elasticsearch.index_prefix', 'ecommerce') . '_products';
    }

    /**
     * Create products index with mapping
     *
     * @return array
     */
    public function createIndex(): array
    {
        $params = [
            'index' => $this->indexName,
            'body' => [
                'settings' => [
                    'number_of_shards' => 1,
                    'number_of_replicas' => 0,
                    'analysis' => [
                        'analyzer' => [
                            'turkish_analyzer' => [
                                'type' => 'standard',
                                'stopwords' => '_turkish_',
                            ],
                        ],
                    ],
                ],
                'mappings' => [
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'name' => [
                            'type' => 'text',
                            'analyzer' => 'turkish_analyzer',
                            'fields' => [
                                'keyword' => ['type' => 'keyword'],
                            ],
                        ],
                        'description' => [
                            'type' => 'text',
                            'analyzer' => 'turkish_analyzer',
                        ],
                        'sku' => ['type' => 'keyword'],
                        'price' => ['type' => 'float'],
                        'stock_quantity' => ['type' => 'integer'],
                        'brand' => [
                            'type' => 'keyword',
                        ],
                        'is_active' => ['type' => 'boolean'],
                        'created_at' => ['type' => 'date'],
                        'updated_at' => ['type' => 'date'],
                    ],
                ],
            ],
        ];

        try {
            return $this->client->indices()->create($params)->asArray();
        } catch (\Exception $e) {
            // Index might already exist
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Index a product
     *
     * @param Product $product
     * @return array
     */
    public function indexProduct(Product $product): array
    {
        $params = [
            'index' => $this->indexName,
            'id' => $product->id,
            'body' => [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'sku' => $product->sku,
                'price' => (float) $product->price,
                'stock_quantity' => $product->stock_quantity,
                'brand' => $product->brand,
                'is_active' => $product->is_active,
                'created_at' => $product->created_at?->toIso8601String(),
                'updated_at' => $product->updated_at?->toIso8601String(),
            ],
        ];

        return $this->client->index($params)->asArray();
    }

    /**
     * Bulk index all products
     *
     * @return array
     */
    public function bulkIndexProducts(): array
    {
        $products = Product::where('is_active', true)->get();
        $params = ['body' => []];

        foreach ($products as $product) {
            $params['body'][] = [
                'index' => [
                    '_index' => $this->indexName,
                    '_id' => $product->id,
                ],
            ];

            $params['body'][] = [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'sku' => $product->sku,
                'price' => (float) $product->price,
                'stock_quantity' => $product->stock_quantity,
                'brand' => $product->brand,
                'is_active' => $product->is_active,
                'created_at' => $product->created_at?->toIso8601String(),
                'updated_at' => $product->updated_at?->toIso8601String(),
            ];
        }

        if (empty($params['body'])) {
            return ['indexed' => 0];
        }

        $response = $this->client->bulk($params)->asArray();

        return [
            'indexed' => count($products),
            'response' => $response,
        ];
    }

    /**
     * Search products
     *
     * @param string $query
     * @param array $filters
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function searchProducts(string $query, array $filters = [], int $page = 1, int $limit = 20): array
    {
        $from = ($page - 1) * $limit;

        $must = [];
        $filter = [];

        // Full-text search query
        if (!empty($query)) {
            $must[] = [
                'multi_match' => [
                    'query' => $query,
                    'fields' => ['name^3', 'description', 'brand^2', 'sku'],
                    'fuzziness' => 'AUTO',
                ],
            ];
        }

        // Always filter active products
        $filter[] = ['term' => ['is_active' => true]];

        // Brand filter
        if (!empty($filters['brand'])) {
            $filter[] = ['term' => ['brand' => $filters['brand']]];
        }

        // Price range filter
        if (isset($filters['minPrice']) || isset($filters['maxPrice'])) {
            $rangeFilter = [];
            if (isset($filters['minPrice'])) {
                $rangeFilter['gte'] = $filters['minPrice'];
            }
            if (isset($filters['maxPrice'])) {
                $rangeFilter['lte'] = $filters['maxPrice'];
            }
            $filter[] = ['range' => ['price' => $rangeFilter]];
        }

        // In stock filter
        if (isset($filters['inStock']) && $filters['inStock']) {
            $filter[] = ['range' => ['stock_quantity' => ['gt' => 0]]];
        }

        $params = [
            'index' => $this->indexName,
            'body' => [
                'from' => $from,
                'size' => $limit,
                'query' => [
                    'bool' => [
                        'must' => $must,
                        'filter' => $filter,
                    ],
                ],
                'sort' => [
                    ['_score' => ['order' => 'desc']],
                    ['created_at' => ['order' => 'desc']],
                ],
            ],
        ];

        try {
            $response = $this->client->search($params)->asArray();

            $hits = $response['hits']['hits'] ?? [];
            $total = $response['hits']['total']['value'] ?? 0;

            $products = array_map(function ($hit) {
                return $hit['_source'];
            }, $hits);

            return [
                'data' => $products,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
            ];
        } catch (\Exception $e) {
            return [
                'data' => [],
                'total' => 0,
                'page' => $page,
                'limit' => $limit,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete a product from index
     *
     * @param int $productId
     * @return array
     */
    public function deleteProduct(int $productId): array
    {
        $params = [
            'index' => $this->indexName,
            'id' => $productId,
        ];

        try {
            return $this->client->delete($params)->asArray();
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Delete the entire index
     *
     * @return array
     */
    public function deleteIndex(): array
    {
        $params = ['index' => $this->indexName];

        try {
            return $this->client->indices()->delete($params)->asArray();
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
