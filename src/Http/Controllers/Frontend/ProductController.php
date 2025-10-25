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

        if (!$product) {
            abort(404);
        }

        return view("frontend.theme.{$this->theme}.products.show", compact('product'));
    }

    /**
     * Display a list of products.
     */
    public function index(): View
    {
        $manager = wncms()
            ->package('wncms-ecommerce')
            ->product();

        $order = gto('wncms_ecommerce_product_index_order', 'id');
        $sequence = gto('wncms_ecommerce_product_index_sequence', 'desc');
        $page_size = gto('wncms_ecommerce_product_index_page_size', 5);
        $cache = gto('wncms_ecommerce_product_index_cache', true);
        
        $products = $manager->getList([
            'order'     => $order,
            'sequence'  => $sequence,
            'page_size' => $page_size,
            'cache'     => $cache,
        ]);

        return view("frontend.theme.{$this->theme}.products.index", compact('products'));
    }
}
