<?php

namespace Secretwebmaster\WncmsEcommerce\Services\Managers;

use Wncms\Services\Managers\ModelManager;
use Secretwebmaster\WncmsEcommerce\Models\Product;
use Illuminate\Database\Eloquent\Builder;

class ProductManager extends ModelManager
{
    protected string $cacheKeyPrefix = 'wncms_ecommerce_product';
    protected string|array $cacheTags = ['products'];

    public function getModelClass(): string
    {
        return Product::class;
    }

    /**
     * Build query for product list with filters and pagination.
     */
    protected function buildListQuery(array $options): Builder
    {
        $q = $this->query();

        // Filters
        $this->applyWhereConditions($q, $options['wheres'] ?? []);
        $this->applyStatus($q, 'status', $options['status'] ?? 'active');
        $this->applyKeywordFilter($q, $options['keywords'] ?? null, ['name', 'slug']);
        $this->applyWebsiteId($q, $options['website_id'] ?? null);
        $this->applyWiths($q, $options['withs'] ?? []);

        // Ordering
        $this->applyOrdering(
            $q,
            $options['order'] ?? 'id',
            $options['sequence'] ?? 'desc',
            $options['is_random'] ?? false
        );

        return $q;
    }
}
