<?php

namespace Secretwebmaster\WncmsEcommerce\Http\Controllers\Frontend;

use Wncms\Http\Controllers\Frontend\FrontendController;
use Illuminate\View\View;

class ProductController extends FrontendController
{
    /**
     * Display a single product.
     */
    public function show(string $slug): View
    {
        $manager = wncms()
            ->package('wncms-ecommerce')
            ->product();

        $product = $manager->get([
            'slug'  => $slug,
            'cache' => true,
        ]);

        abort_unless($product, 404);

        return $this->view(
            "frontend.themes.{$this->theme}.products.show",
            compact('product'),
            'wncms-ecommerce::frontend.products.show'
        );
    }

    /**
     * Display product list.
     */
    public function index(): View
    {
        $manager = wncms()
            ->package('wncms-ecommerce')
            ->product();

        $products = $manager->getList([
            'order'     => gto('wncms_ecommerce_product_index_order', 'id'),
            'sequence'  => gto('wncms_ecommerce_product_index_sequence', 'desc'),
            'page_size' => gto('wncms_ecommerce_product_index_page_size', 5),
            'cache'     => gto('wncms_ecommerce_product_index_cache', true),
        ]);

        return $this->view(
            "frontend.themes.{$this->theme}.products.index",
            compact('products'),
            'wncms-ecommerce::frontend.products.index'
        );
    }

    /**
     * Display products under any tag type (category, tag, brand, etc.)
     */
    public function tag(string $type, string $slug): View
    {
        // 1. Get tag metas for Product model
        $productClass = wncms()->getModelClass('product');
        $metas = $productClass::getTagMeta();

        // 2. Ensure short exists
        $meta = collect($metas)->firstWhere('short', $type);
        abort_unless($meta, 404);

        // actual full key, e.g. product_category
        $fullKey = $meta['key'];

        // 3. Fetch actual tag model
        $tag = wncms()->tag()->get(['slug' => $slug, 'type' => $fullKey]);
        abort_unless($tag, 404);

        // 4. Fetch products under this tag
        $manager = wncms()->package('wncms-ecommerce')->product();

        $products = $manager->getList([
            'tags'     => [$tag->name],
            'tag_type' => $fullKey,      // e.g. product_category
            'status'   => 'active',
            'page_size' => 10,
        ]);

        return $this->view(
            "frontend.themes.{$this->theme}.products.tag",
            compact('tag', 'type', 'products'),
            'wncms-ecommerce::frontend.products.tag'
        );
    }
}
