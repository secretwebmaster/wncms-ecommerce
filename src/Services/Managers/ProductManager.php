<?php

namespace Secretwebmaster\WncmsEcommerce\Services\Managers;

use Wncms\Services\Managers\ModelManager;
use Secretwebmaster\WncmsEcommerce\Models\Product;
use Illuminate\Database\Eloquent\Builder;

class ProductManager extends ModelManager
{
    protected string $cacheKeyPrefix = 'wncms_ecommerce_product';
    protected string|array $cacheTags = ['products'];
    protected string $packageKey = 'wncms-ecommerce';

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
        $this->applyIds($q, 'products.id', $options['ids'] ?? []);
        $this->applyExcludeIds($q, 'products.id', $options['excluded_ids'] ?? []);
        $this->applyExcludedTags($q, $options['excluded_tag_ids'] ?? []);
        $this->applyTagFilter($q, $options['tags'] ?? [], $options['tag_type'] ?? null);
        $this->applyWhereConditions($q, $options['wheres'] ?? []);
        $this->applyStatus($q, 'status', $options['status'] ?? 'active');
        $this->applyKeywordFilter($q, $options['keywords'] ?? null, ['name', 'slug']);
        $this->applyWiths($q, $options['withs'] ?? []);
        // $this->applyOrdering($q, $options['order'] ?? 'order', $options['sequence'] ?? 'desc', ($options['order'] ?? '') === 'random');
        $this->applyWebsiteId($q, $options['website_id'] ?? null);

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
