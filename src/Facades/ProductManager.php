<?php

namespace Secretwebmaster\WncmsEcommerce\Facades;

use Illuminate\Support\Facades\Facade;

class ProductManager extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'product-manager';
    }
}
